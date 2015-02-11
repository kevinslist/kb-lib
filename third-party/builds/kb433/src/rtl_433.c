/*
 * rtl_433, turns your Realtek RTL2832 based DVB dongle into a 433.92MHz generic data receiver
 * Copyright (C) 2012 by Benjamin Larsson <benjamin@southpole.se>
 *
 * Based on rtl_sdr
 *
 * Copyright (C) 2012 by Steve Markgraf <steve@steve-m.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "rtl-sdr.h"
#include "rtl_433.h"

static int do_exit = 0;
static int do_exit_async = 0, frequencies = 0, events = 0;
uint32_t frequency[MAX_PROTOCOLS];
time_t rawtime_old;
int flag;
uint32_t samp_rate = DEFAULT_SAMPLE_RATE;
static uint32_t bytes_to_read = 0;
static rtlsdr_dev_t *dev = NULL;
static uint16_t scaled_squares[256];
static int override_short = 0;
static int override_long = 0;
int debug_output = 0;

int max_pulse_limit = 0;
unsigned int current_pulse_count = 0;
unsigned int current_silent_count = 0;
unsigned int count_since_last_pulse = 0;

struct timeval kb_current_time_of_day;
unsigned int kb_current_time = 0;
int kb_list_devices = 0;

static uint16_t lp_xmem[FILTER_ORDER] = {0};

#define F_SCALE 15
#define S_CONST (1<<F_SCALE)
#define FIX(x) ((int)(x*S_CONST))

int a[FILTER_ORDER + 1] = {FIX(1.00000), FIX(0.96907)};
int b[FILTER_ORDER + 1] = {FIX(0.015466), FIX(0.015466)};


static unsigned int counter = 0;
static unsigned int print = 1;
static unsigned int print2 = 0;
static unsigned int pulses_found = 0;
static unsigned int prev_pulse_start = 0;
static unsigned int pulse_start = 0;
static unsigned int pulse_end = 0;
static unsigned int pulse_avg = 0;
static unsigned int signal_start = 0;
static unsigned int signal_end = 0;
static unsigned int signal_pulse_data[4000][3] = {
  {0}
};
static unsigned int signal_pulse_counter = 0;

struct protocol_state {
  int (*callback)(uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS], int16_t bits_per_row[BITBUF_ROWS]);

  /* bits state */
  int bits_col_idx;
  int bits_row_idx;
  int bits_bit_col_idx;
  uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS];
  int16_t bits_per_row[BITBUF_ROWS];
  int bit_rows;
  unsigned int modulation;

  /* demod state */
  int pulse_length;
  int pulse_count;
  int pulse_distance;
  int sample_counter;
  int start_c;

  int packet_present;
  int pulse_start;
  int real_bits;
  int start_bit;
  /* pwm limits */
  int short_limit;
  int long_limit;
  int reset_limit;


};

struct dm_state {
  FILE *file;
  int save_data;
  int32_t level_limit;
  int32_t decimation_level;
  int16_t filter_buffer[MAXIMAL_BUF_LENGTH + FILTER_ORDER];
  int16_t* f_buf;
  int analyze;
  int debug_mode;

  /* Signal grabber variables */
  int signal_grabber;
  int8_t* sg_buf;
  int sg_index;
  int sg_len;


  /* Protocol states */
  int r_dev_num;
  struct protocol_state *r_devs[MAX_PROTOCOLS];

};

/* precalculate lookup table for envelope detection */
static void calc_squares() {
  int i;
  for (i = 0; i < 256; i++)
    scaled_squares[i] = (128 - i) * (128 - i);
}

/** This will give a noisy envelope of OOK/ASK signals
 *  Subtract the bias (-128) and get an envelope estimation
 *  The output will be written in the input buffer
 *  @returns   pointer to the input buffer
 */

static void envelope_detect(unsigned char *buf, uint32_t len, int decimate) {
  uint16_t* sample_buffer = (uint16_t*) buf;
  unsigned int i;
  unsigned op = 0;
  unsigned int stride = 1 << decimate;

  for (i = 0; i < len / 2; i += stride) {
    sample_buffer[op++] = scaled_squares[buf[2 * i ]] + scaled_squares[buf[2 * i + 1]];
  }
}

/** Something that might look like a IIR lowpass filter
 *
 *  [b,a] = butter(1, 0.01) ->  quantizes nicely thus suitable for fixed point
 *  Q1.15*Q15.0 = Q16.15
 *  Q16.15>>1 = Q15.14
 *  Q15.14 + Q15.14 + Q15.14 could possibly overflow to 17.14
 *  but the b coeffs are small so it wont happen
 *  Q15.14>>14 = Q15.0 \o/
 */

static void low_pass_filter(uint16_t *x_buf, int16_t *y_buf, uint32_t len) {
  unsigned int i;

  /* Calculate first sample */
  y_buf[0] = ((a[1] * y_buf[-1] >> 1) + (b[0] * x_buf[0] >> 1) + (b[1] * lp_xmem[0] >> 1)) >> (F_SCALE - 1);
  for (i = 1; i < len; i++) {
    y_buf[i] = ((a[1] * y_buf[i - 1] >> 1) + (b[0] * x_buf[i] >> 1) + (b[1] * x_buf[i - 1] >> 1)) >> (F_SCALE - 1);
  }

  /* Save last sample */
  memcpy(lp_xmem, &x_buf[len - 1 - FILTER_ORDER], FILTER_ORDER * sizeof (int16_t));
  memcpy(&y_buf[-FILTER_ORDER], &y_buf[len - 1 - FILTER_ORDER], FILTER_ORDER * sizeof (int16_t));
  //fprintf(stderr, "%d\n", y_buf[0]);
}

static void rtlsdr_callback(unsigned char *buf, uint32_t len, void *ctx) {
  struct dm_state *demod = ctx;
  uint16_t* sbuf = (uint16_t*) buf;
  unsigned int i;

  envelope_detect(buf, len, demod->decimation_level);
  low_pass_filter(sbuf, demod->f_buf, len >> (demod->decimation_level + 1));

  //pwm_analyze(demod, demod->f_buf, len / 2);

  //int max_pulse_limit = 0;
  //int current_pulse_count = 0;
  //int current_silent_count = 0;

  for (i = 0; i < (len / 2); i++) {
    if (demod->f_buf[i] > demod->level_limit || (current_pulse_count > 0 && demod->f_buf[i] > (demod->level_limit - 1000))) {
      if (current_silent_count > 0) {
        fprintf(stderr, "!%d\n", current_silent_count);
        current_silent_count = 0;
      }
      current_pulse_count++;
      if (demod->f_buf[i] > max_pulse_limit) {
        max_pulse_limit = demod->f_buf[i];
      }
    } else {
      if (current_pulse_count > 0) {
        gettimeofday(&kb_current_time_of_day, NULL);
        kb_current_time = kb_current_time_of_day.tv_sec * 1000; // sec to ms
        kb_current_time += kb_current_time_of_day.tv_usec / 1000; // us to ms
        kb_current_time = kb_current_time / 100;
        fprintf(stderr, ":%d:%d:%d\n", current_pulse_count, max_pulse_limit, kb_current_time);
        current_pulse_count = 0;
        max_pulse_limit = 0;
        count_since_last_pulse = 0;
      }
      current_silent_count++;
      if (count_since_last_pulse < 20) {

        if (current_silent_count > 2000) {
          count_since_last_pulse++;
          fprintf(stderr, "!%d\n", current_silent_count);
          current_silent_count = 0;
        }
      } else {
        if (current_silent_count > 5000000) {
          fprintf(stderr, "!%d\n", current_silent_count);
          current_silent_count = 0;
        }
      }
    }
  }



}

void usage(void) {
  fprintf(stderr,
    "rtl_433, an ISM band generic data receiver for RTL2832 based DVB-T receivers\n\n"
    "Usage:\t[-d device_index (default: 0)]\n"
    "\t[-g gain (default: 0 for auto)]\n"
    "\t[-a analyze mode, print a textual description of the signal]\n"
    "\t[-t signal auto save, use it together with analyze mode (-a -t)\n"
    "\t[-l change the detection level used to determine pulses (0-3200) default: %i]\n"
    "\t[-f [-f...] receive frequency[s], default: %i Hz]\n"
    "\t[-s samplerate (default: %i Hz)]\n"
    "\t[-S force sync output (default: async)]\n"
    "\t[-r read data from file instead of from a receiver]\n"
    "\t[-p ppm_error (default: 0)]\n"
    "\t[-r test file name (indata)]\n"
    "\t[-m test file mode (0 rtl_sdr data, 1 rtl_433 data)]\n"
    "\t[-D print debug info on event\n"
    "\t[-z override short value\n"
    "\t[-x override long value\n"
    "\tfilename (a '-' dumps samples to stdout)\n\n", DEFAULT_LEVEL_LIMIT, DEFAULT_FREQUENCY, DEFAULT_SAMPLE_RATE);
  exit(1);
}

#ifdef _WIN32

BOOL WINAPI
sighandler(int signum) {
  if (CTRL_C_EVENT == signum) {
    fprintf(stderr, "Signal caught, exiting!\n");
    do_exit = 1;
    rtlsdr_cancel_async(dev);
    return TRUE;
  }
  return FALSE;
}
#else

static void sighandler(int signum) {
  if (signum == SIGPIPE) {
    signal(SIGPIPE, SIG_IGN);
  } else {
    fprintf(stderr, "Signal caught, exiting!\n");
  }
  do_exit = 1;
  rtlsdr_cancel_async(dev);
}
#endif

int main(int argc, char **argv) {
#ifndef _WIN32
  struct sigaction sigact;
#endif
  char *filename = NULL;
  char *test_mode_file = NULL;
  FILE *test_mode;
  int n_read;
  int r, opt;
  int i, gain = 0;
  int sync_mode = 0;
  int ppm_error = 0;
  struct dm_state* demod;
  uint8_t *buffer;
  uint32_t dev_index = 0;
  int frequency_current = 0;
  uint32_t out_block_size = DEFAULT_BUF_LENGTH;
  int device_count;
  char vendor[256], product[256], serial[256];

  demod = malloc(sizeof (struct dm_state));
  memset(demod, 0, sizeof (struct dm_state));

  /* initialize tables */
  calc_squares();

  demod->f_buf = &demod->filter_buffer[FILTER_ORDER];
  demod->decimation_level = DEFAULT_DECIMATION_LEVEL;
  demod->level_limit = DEFAULT_LEVEL_LIMIT;

  frequency[0] = DEFAULT_FREQUENCY;
  frequencies = 1;

  while ((opt = getopt(argc, argv, "x:z:p:Dtam:r:c:l:d:f:g:s:k::")) != -1) {
    switch (opt) {
      case 'd':
        dev_index = atoi(optarg);
        break;
      case 'f':
        frequency[0] = (uint32_t) atof(optarg);
        break;
      case 'g':
        gain = (int) (atof(optarg) * 10); /* tenths of a dB */
        break;
      case 'p':
        ppm_error = atoi(optarg);
        break;
      case 's':
        samp_rate = (uint32_t) atof(optarg);
        break;
      case 'k':
        kb_list_devices = 1;
        break;
      case 'l':
        demod->level_limit = (uint32_t) atof(optarg);
        break;
      case 'c':
        demod->decimation_level = (uint32_t) atof(optarg);
        break;
      default:
        usage();
        break;
    }
  }

  demod->analyze = 1;

  buffer = malloc(out_block_size * sizeof (uint8_t));

  device_count = rtlsdr_get_device_count();
  if (!device_count) {
    fprintf(stderr, "No supported devices found.\n");
    exit(1);
  }

  fprintf(stderr, "%d\n", device_count);
  for (i = 0; i < device_count; i++) {
    rtlsdr_get_device_usb_strings(i, vendor, product, serial);
    fprintf(stderr, "%d:%s:%s:%s\n", i, vendor, product, serial);
  }
  if (kb_list_devices) {
    exit(1);
  }

  fprintf(stderr, "Using device %d: %s\n",
    dev_index, rtlsdr_get_device_name(dev_index));

  r = rtlsdr_open(&dev, dev_index);
  if (r < 0) {
    fprintf(stderr, "#Failed to open rtlsdr device #%d.\n", dev_index);
    if (!test_mode_file)
      exit(1);
  }
#ifndef _WIN32
  sigact.sa_handler = sighandler;
  sigemptyset(&sigact.sa_mask);
  sigact.sa_flags = 0;
  sigaction(SIGINT, &sigact, NULL);
  sigaction(SIGTERM, &sigact, NULL);
  sigaction(SIGQUIT, &sigact, NULL);
  sigaction(SIGPIPE, &sigact, NULL);
#else
  SetConsoleCtrlHandler((PHANDLER_ROUTINE) sighandler, TRUE);
#endif
  /* Set the sample rate */
  r = rtlsdr_set_sample_rate(dev, samp_rate);
  if (r < 0)
    fprintf(stderr, "#WARNING: Failed to set sample rate.\n");
  else
    fprintf(stderr, "#Sample rate set to %d.\n", rtlsdr_get_sample_rate(dev)); // Unfortunately, doesn't return real rate

  fprintf(stderr, "#Sample rate decimation set to %d. %d->%d\n", demod->decimation_level, samp_rate, samp_rate >> demod->decimation_level);
  fprintf(stderr, "#Bit detection level set to %d.\n", demod->level_limit);

  if (0 == gain) {
    /* Enable automatic gain */
    r = rtlsdr_set_tuner_gain_mode(dev, 0);
    if (r < 0)
      fprintf(stderr, "#WARNING: Failed to enable automatic gain.\n");
    else
      fprintf(stderr, "#Tuner gain set to Auto.\n");
  } else {
    /* Enable manual gain */
    r = rtlsdr_set_tuner_gain_mode(dev, 1);
    if (r < 0)
      fprintf(stderr, "#WARNING: Failed to enable manual gain.\n");

    /* Set the tuner gain */
    r = rtlsdr_set_tuner_gain(dev, gain);
    if (r < 0)
      fprintf(stderr, "#WARNING: Failed to set tuner gain.\n");
    else
      fprintf(stderr, "#Tuner gain set to %f dB.\n", gain / 10.0);
  }

  r = rtlsdr_set_freq_correction(dev, ppm_error);

  demod->save_data = 0;

  /* Reset endpoint before we start reading from it (mandatory) */
  r = rtlsdr_reset_buffer(dev);
  if (r < 0) {
    fprintf(stderr, "#WARNING: Failed to reset buffers.\n");
  }

  fprintf(stderr, "#Reading samples in async mode...\n");
  while (!do_exit) {
    /* Set the frequency */
    r = rtlsdr_set_center_freq(dev, frequency[frequency_current]);
    if (r < 0)
      fprintf(stderr, "#WARNING: Failed to set center freq.\n");
    else
      fprintf(stderr, "#Tuned to %u Hz.\n", rtlsdr_get_center_freq(dev));

    fprintf(stderr, "###kb433_start###\n");
    r = rtlsdr_read_async(dev, rtlsdr_callback, (void *) demod,DEFAULT_ASYNC_BUF_NUMBER, out_block_size);
    do_exit_async = 0;
    frequency_current++;
    if (frequency_current > frequencies - 1) frequency_current = 0;
  }

  if (do_exit)
    fprintf(stderr, "#\n#User cancel, exiting...\n");
  else
    fprintf(stderr, "\nLibrary error %d, exiting...\n", r);

  for (i = 0; i < demod->r_dev_num; i++)
    free(demod->r_devs[i]);

  if (demod)
    free(demod);

  rtlsdr_close(dev);
  free(buffer);
out:
  return r >= 0 ? r : -r;
}

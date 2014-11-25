#include <errno.h>
#include <signal.h>
#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <sys/time.h>
#include <unistd.h>
#include "rtl-sdr.h"


#define _POSIX_C_SOURCE 200809L                 
#define DEFAULT_SAMPLE_RATE     250000
#define DEFAULT_FREQUENCY       433882000
#define DEFAULT_ASYNC_BUF_NUMBER    32
#define DEFAULT_BUF_LENGTH      (16 * 16384)
#define DEFAULT_LEVEL_LIMIT     10000
#define DEFAULT_DECIMATION_LEVEL 0
#define MINIMAL_BUF_LENGTH      512
#define MAXIMAL_BUF_LENGTH      (256 * 16384)
#define FILTER_ORDER            1
#define MAX_PROTOCOLS           10
#define SIGNAL_GRABBER_BUFFER   (12 * DEFAULT_BUF_LENGTH)
#define BITBUF_COLS             34
#define BITBUF_ROWS             50

static int do_exit = 0;
static int do_exit_async = 0, frequencies = 0, events = 0;
uint32_t frequency[MAX_PROTOCOLS];
time_t rawtime_old;
int flag;
uint32_t samp_rate = DEFAULT_SAMPLE_RATE;

struct timeval t1, t2;
struct timeval t_block_special_previous, t_block_special_current;
double elapsedTime;

static uint32_t def_frequency = 433882000;

static uint32_t bytes_to_read = 0;
static rtlsdr_dev_t *dev = NULL;
static uint16_t scaled_squares[256];
static int debug_output = 0;
static int override_short = 0;
static int override_long = 0;

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

static uint16_t lp_xmem[FILTER_ORDER] = {0};


#define F_SCALE 15
#define S_CONST (1<<F_SCALE)
#define FIX(x) ((int)(x*S_CONST))

int a[FILTER_ORDER + 1] = {FIX(1.00000), FIX(0.96907)};
int b[FILTER_ORDER + 1] = {FIX(0.015466), FIX(0.015466)};

typedef struct {
  unsigned int id;
  char name[256];
  unsigned int modulation;
  unsigned int short_limit;
  unsigned int long_limit;
  unsigned int reset_limit;
  int (*json_callback)(uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS]);
} r_device;

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

};

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

static void sighandler() {
  fprintf(stderr, "Signal caught, exiting!\n");
  do_exit = 1;
  rtlsdr_cancel_async(dev);
}

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

void append_code(char* s, char c) {
  int len = strlen(s);
  s[len] = c;
  s[len + 1] = '\0';
}


char last_remote_code[256];
char last_signal_code[256];
char current_remote_code[256];
char current_signal_code[256];

static void send_signal() {

  unsigned int i;
  unsigned int previous_pulse_end_kb = 0;
  unsigned int pulse_space = 0;
  unsigned int has_remote_code = 0;
  unsigned int is_repeat_code = 0;
  unsigned int has_repeat_code = 0;
  unsigned int has_signal_code = 0;

  unsigned int remote_code_total = 0;
  unsigned int remote_code_average = 0;


  unsigned int signal_pulse_distance_total = 0;
  unsigned int signal_pulse_distance_average = 0;

  unsigned int signal_pulse_length_total = 0;
  unsigned int signal_pulse_length_average = 0;



  char next_bit = '0';
  memset(current_remote_code, 0, strlen(current_remote_code));

  if (signal_pulse_counter > 9) {
    has_remote_code = 1;
    if (signal_pulse_counter > 26) {
      has_signal_code = 1;
    }
  }
  if (signal_pulse_counter == 2 || signal_pulse_counter == 12) {
    is_repeat_code = 1;
    if (signal_pulse_counter == 12) {
      has_remote_code = 1;
    }
  }

  unsigned int pulse_max_length = 0;
  unsigned int pulse_min_length = 11110;
  unsigned int pulse_max_diff = 0;
  unsigned int pulse_min_diff = 0;
  unsigned int pulse_max_min_diff = 0;

  if (has_remote_code) {
    //fprintf(stderr, "has_remote_code!!\n");
    unsigned int remote_code_offset = (signal_pulse_counter == 12) ? 2 : 0;
    unsigned int remote_code_pulse_count = 0;

    previous_pulse_end_kb = signal_pulse_data[remote_code_offset + 1][1];
    //fprintf(stderr, "remote_code_offset:%d!!\n", remote_code_offset);
    for (i = (2 + remote_code_offset); i < (10 + remote_code_offset); i++) {
      if (signal_pulse_data[i][2] < 100) {
        pulse_space = signal_pulse_data[i][0] - previous_pulse_end_kb;
        if (pulse_space > pulse_max_length) {
          pulse_max_length = pulse_space;
        }
        if (pulse_space < pulse_min_length) {
          pulse_min_length = pulse_space;
        }
        remote_code_total += pulse_space;
        previous_pulse_end_kb = pulse_space = signal_pulse_data[i][1];
        remote_code_pulse_count++;
      }
    }
    //fprintf(stderr, "RC PULSE COUNT:%d\n", remote_code_pulse_count);

    if (8 == remote_code_pulse_count) {
      remote_code_average = remote_code_total / 8;
      if (remote_code_average > 30) {
        //fprintf(stderr, "RCAremote_code_average:%d\n", remote_code_average);

        previous_pulse_end_kb = signal_pulse_data[remote_code_offset + 1][1];
        for (i = (2 + remote_code_offset); i < (10 + remote_code_offset); i++) {
          pulse_space = signal_pulse_data[i][0] - previous_pulse_end_kb;
          pulse_max_diff = pulse_max_length - pulse_space;
          pulse_min_diff = pulse_space - pulse_min_length;

          //fprintf(stderr, "PL:PS %d:%d\n", signal_pulse_data[i][2], pulse_space);
          //fprintf(stderr, "DX/DM# %d/%d\n", pulse_max_diff, pulse_min_diff);
          if (pulse_max_diff < pulse_min_diff) {
            next_bit = '1';
          } else {
            next_bit = '0';
          }
          append_code(current_remote_code, next_bit);
          previous_pulse_end_kb = pulse_space = signal_pulse_data[i][1];
        }
        //fprintf(stderr, ":SCRC:%s\n", current_remote_code);
      }
    }
  }

  if (is_repeat_code) {
    if (strlen(current_remote_code) == 0) {
      //fprintf(stderr, "COPY LAST REMOTE:%s\n", last_remote_code);
      strcpy(current_remote_code, last_remote_code);
    }
    fprintf(stderr, "#%s:%f\n", current_remote_code, elapsedTime);

  } else if (has_signal_code) {
    memset(current_signal_code, 0, strlen(current_signal_code));
    signal_pulse_length_total = signal_pulse_data[11][2];

    // DETERMINE AVERAGE PULSE LENGTH to test for end of signal: found_signal_gap
    for (i = 12; i < 25; i++) {
      signal_pulse_length_total += signal_pulse_data[i][2];
    }
    signal_pulse_length_average = signal_pulse_length_total / 14;

    //fprintf(stderr, "signal_pulse_length_average||%d\n", signal_pulse_length_average);

    unsigned int found_signal_gap = 0;
    unsigned int signal_length = 0;
    previous_pulse_end_kb = signal_pulse_data[11][1];

    pulse_max_length = 0;
    pulse_min_length = 11110;
    pulse_max_diff = 0;
    pulse_min_diff = 0;
  
    // DETERMINE AVERage PULSE DISTANCE
    // && !found_signal_gap
    //fprintf(stderr, "signal_pulse_counter:%d\n", signal_pulse_counter);
    
    for (i = 12; i < signal_pulse_counter && !found_signal_gap; i++) {
      if (signal_pulse_data[i][2] > (signal_pulse_length_average - 25) && signal_pulse_data[i][2] < (signal_pulse_length_average + 25)) {
        pulse_space = signal_pulse_data[i][0] - previous_pulse_end_kb;
        
        //fprintf(stderr, "P|#%d| %d:%d\n", signal_length, signal_pulse_data[i][2], pulse_space);
        
        if(pulse_space > pulse_max_length){
          pulse_max_length = pulse_space;
        }
        if(pulse_space < pulse_min_length){
          pulse_min_length = pulse_space;
        }
        signal_pulse_distance_total += pulse_space;
        signal_length++;
      } else {
        found_signal_gap = i - 12;
      }
      previous_pulse_end_kb = signal_pulse_data[i][1];
    }

    //fprintf(stderr, "signal_length|%d\n", signal_length);
    
    if (signal_length == 16 || signal_length == 32 || signal_length == 33) {
      signal_pulse_distance_average = signal_pulse_distance_total / signal_length;
      // DETERMINE SIGNAL CODE
      
      
      //fprintf(stderr, "SIGDIFF# %d\n", (pulse_max_length - pulse_min_length));
      pulse_max_min_diff = (pulse_max_length - pulse_min_length);
      previous_pulse_end_kb = signal_pulse_data[11][1];
      for (i = 12; i < (12 + signal_length); i++) {
        pulse_space = signal_pulse_data[i][0] - previous_pulse_end_kb;
        //fprintf(stderr, "SIGNAL pulse_space||%d\n", pulse_space);
        pulse_max_diff = pulse_max_length - pulse_space;
        pulse_min_diff = pulse_space - pulse_min_length;

        //fprintf(stderr, "PL:PS %d:%d\n", signal_pulse_data[i][2], pulse_space);
        
        if (pulse_max_diff < pulse_min_diff || (pulse_max_min_diff < 50)) {
          next_bit = '1';
        } else {
          next_bit = '0';
        }
        /*
        if (signal_pulse_distance_average > pulse_space) {
          next_bit = '0';
        } else {
          next_bit = '1';
        }
         */
        append_code(current_signal_code, next_bit);
        previous_pulse_end_kb = signal_pulse_data[i][1];
      }

      fprintf(stderr, "#%s:%s:%f\n", current_remote_code, current_signal_code, elapsedTime);

      strcpy(last_remote_code, current_remote_code);
      strcpy(last_signal_code, current_signal_code);
    }
  }
}

static void pwm_analyze(struct dm_state *demod, int16_t *buf, uint32_t len) {
  unsigned int i;
  unsigned int ct;
  
  gettimeofday(&t_block_special_current, NULL);
  if ((t_block_special_current.tv_sec - t_block_special_previous.tv_sec) >= 2) {
    fprintf(stderr, "\n");
    gettimeofday(&t_block_special_current, NULL);
    gettimeofday(&t_block_special_previous, NULL);
  }
  for (i = 0; i < len; i++) {
    if (buf[i] > demod->level_limit) {
      gettimeofday(&t_block_special_previous, NULL);
      if (!signal_start) {
        counter = 0;
        signal_start = 1;
      }
      if (print) {
        pulses_found++;
        pulse_start = counter;
        signal_pulse_data[signal_pulse_counter][0] = counter;
        signal_pulse_data[signal_pulse_counter][1] = -1;
        signal_pulse_data[signal_pulse_counter][2] = -1;


        //fprintf(stderr, "#%d:%d:%d:%d:%d:", pulses_found, (counter-pulse_end), (buf[i]), counter, pulse_start-prev_pulse_start);

        prev_pulse_start = pulse_start;
        print = 0;
        print2 = 1;
      }
    }
    counter++;
    if (buf[i] < demod->level_limit) {
      if (print2) {
        gettimeofday(&t_block_special_previous, NULL);
        gettimeofday(&t2, NULL);
        elapsedTime = (t2.tv_sec - t1.tv_sec) * 1000.0; // sec to ms
        elapsedTime += (t2.tv_usec - t1.tv_usec) / 1000.0; // us to ms
        if ((t2.tv_sec - t1.tv_sec) > 1000) {
          fprintf(stderr, "reset time\n");
          gettimeofday(&t1, NULL);
          gettimeofday(&t2, NULL);
        }
        ct = (int) elapsedTime;
        pulse_avg += counter - pulse_start;
        //fprintf(stderr, "%d:%d;", counter-pulse_start, ct);
        pulse_end = counter;
        print2 = 0;
        signal_pulse_data[signal_pulse_counter][1] = counter;
        signal_pulse_data[signal_pulse_counter][2] = counter - pulse_start;
        signal_pulse_counter++;
        if (signal_pulse_counter >= 4000) {
          signal_pulse_counter = 0;
          goto err;
        }
      }
      print = 1;
      if (signal_start && (pulse_end + 6000 < counter)) {
        send_signal();
        pulses_found = 0;
        signal_pulse_counter = 0;
        pulse_end = 0;
        prev_pulse_start = 0;
        signal_start = 0;
      }
    }
  }
  return;

err:
  fprintf(stderr, "To many pulses detected, probably bad input data or input parameters\n");
  return;
}

static void low_pass_filter(uint16_t *x_buf, int16_t *y_buf, uint32_t len) {
  unsigned int i;
  y_buf[0] = ((a[1] * y_buf[-1] >> 1) + (b[0] * x_buf[0] >> 1) + (b[1] * lp_xmem[0] >> 1)) >> (F_SCALE - 1);
  for (i = 1; i < len; i++) {
    y_buf[i] = ((a[1] * y_buf[i - 1] >> 1) + (b[0] * x_buf[i] >> 1) + (b[1] * x_buf[i - 1] >> 1)) >> (F_SCALE - 1);
  }
  memcpy(lp_xmem, &x_buf[len - 1 - FILTER_ORDER], FILTER_ORDER * sizeof (int16_t));
  memcpy(&y_buf[-FILTER_ORDER], &y_buf[len - 1 - FILTER_ORDER], FILTER_ORDER * sizeof (int16_t));
}

static void rtlsdr_callback(unsigned char *buf, uint32_t len, void *ctx) {
  struct dm_state *demod = ctx;
  uint16_t* sbuf = (uint16_t*) buf;
  int i;
  if (demod->file || !demod->save_data) {
    if (do_exit || do_exit_async)
      return;

    if ((bytes_to_read > 0) && (bytes_to_read < len)) {
      len = bytes_to_read;
      do_exit = 1;
      rtlsdr_cancel_async(dev);
    }

    if (demod->debug_mode == 0) {
      envelope_detect(buf, len, demod->decimation_level);
      low_pass_filter(sbuf, demod->f_buf, len >> (demod->decimation_level + 1));
    }

    if (demod->analyze) {
      pwm_analyze(demod, demod->f_buf, len / 2);
    }

    if (bytes_to_read > 0) {
      bytes_to_read -= len;
    }
  }
}

int main(int argc, char **argv) {
  struct sigaction sigact;
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
  gettimeofday(&t1, NULL);
  while ((opt = getopt(argc, argv, "x:z:p:Dtam:r:c:l:d:f:g:s:b:n:S::")) != -1) {
    switch (opt) {
      case 'd':
        dev_index = atoi(optarg);
        break;
      case 'f':
        //if(frequencies<MAX_PROTOCOLS) frequency[frequencies++] = (uint32_t)atof(optarg);
        //else fprintf(stderr, "Max number of frequencies reached %d\n",MAX_PROTOCOLS);

        def_frequency = (uint32_t) atof(optarg);
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
      case 'b':
        out_block_size = (uint32_t) atof(optarg);
        break;
      case 'l':
        demod->level_limit = (uint32_t) atof(optarg);
        break;
      case 'n':
        bytes_to_read = (uint32_t) atof(optarg) * 2;
        break;
      case 'c':
        demod->decimation_level = (uint32_t) atof(optarg);
        break;
      case 'a':
        demod->analyze = 1;
        break;
      case 'r':
        test_mode_file = optarg;
        break;
      case 't':
        demod->signal_grabber = 1;
        break;
      case 'm':
        demod->debug_mode = atoi(optarg);
        break;
      case 'S':
        sync_mode = 1;
        break;
      case 'D':
        debug_output = 1;
        break;
      case 'z':
        override_short = atoi(optarg);
        break;
      case 'x':
        override_long = atoi(optarg);
        break;
      default:
        usage();
        break;
    }
  }

  if (argc <= optind - 1) {
    usage();
  } else {
    filename = argv[optind];
  }

  gettimeofday(&t1, NULL);
  gettimeofday(&t2, NULL);
  gettimeofday(&t_block_special_previous, NULL);
  gettimeofday(&t_block_special_current, NULL);
        
  if (out_block_size < MINIMAL_BUF_LENGTH ||
          out_block_size > MAXIMAL_BUF_LENGTH) {
    fprintf(stderr,
            "Output block size wrong value, falling back to default\n");
    fprintf(stderr,
            "Minimal length: %u\n", MINIMAL_BUF_LENGTH);
    fprintf(stderr,
            "Maximal length: %u\n", MAXIMAL_BUF_LENGTH);
    out_block_size = DEFAULT_BUF_LENGTH;
  }

  buffer = malloc(out_block_size * sizeof (uint8_t));

  device_count = rtlsdr_get_device_count();
  if (!device_count) {
    fprintf(stderr, "No supported devices found.\n");
    if (!test_mode_file)
      exit(1);
  }

  fprintf(stderr, "Found %d device(s):\n", device_count);
  for (i = 0; i < device_count; i++) {
    rtlsdr_get_device_usb_strings(i, vendor, product, serial);
    fprintf(stderr, "  %d:  %s, %s, SN: %s\n", i, vendor, product, serial);
  }
  fprintf(stderr, "\n");

  fprintf(stderr, "Using device %d: %s\n",
          dev_index, rtlsdr_get_device_name(dev_index));

  r = rtlsdr_open(&dev, dev_index);
  if (r < 0) {
    fprintf(stderr, "Failed to open rtlsdr device #%d.\n", dev_index);
    if (!test_mode_file)
      exit(1);
  }

  sigact.sa_handler = sighandler;
  sigemptyset(&sigact.sa_mask);
  sigact.sa_flags = 0;
  sigaction(SIGINT, &sigact, NULL);
  sigaction(SIGTERM, &sigact, NULL);
  sigaction(SIGQUIT, &sigact, NULL);
  sigaction(SIGPIPE, &sigact, NULL);

  /* Set the sample rate */
  r = rtlsdr_set_sample_rate(dev, samp_rate);
  if (r < 0) {
    fprintf(stderr, "WARNING: Failed to set sample rate.\n");
  } else {
    fprintf(stderr, "Sample rate set to %d.\n", rtlsdr_get_sample_rate(dev)); // Unfortunately, doesn't return real rate
  }

  fprintf(stderr, "Sample rate decimation set to %d. %d->%d\n", demod->decimation_level, samp_rate, samp_rate >> demod->decimation_level);
  fprintf(stderr, "Bit detection level set to %d.\n", demod->level_limit);

  if (0 == gain) {
    /* Enable automatic gain */
    r = rtlsdr_set_tuner_gain_mode(dev, 0);
    if (r < 0)
      fprintf(stderr, "WARNING: Failed to enable automatic gain.\n");
    else
      fprintf(stderr, "Tuner gain set to Auto.\n");
  } else {
    /* Enable manual gain */
    r = rtlsdr_set_tuner_gain_mode(dev, 1);
    if (r < 0)
      fprintf(stderr, "WARNING: Failed to enable manual gain.\n");

    /* Set the tuner gain */
    r = rtlsdr_set_tuner_gain(dev, gain);
    if (r < 0)
      fprintf(stderr, "WARNING: Failed to set tuner gain.\n");
    else
      fprintf(stderr, "Tuner gain set to %f dB.\n", gain / 10.0);
  }

  r = rtlsdr_set_freq_correction(dev, ppm_error);

  demod->save_data = 0;



  /* Reset endpoint before we start reading from it (mandatory) */
  r = rtlsdr_reset_buffer(dev);
  if (r < 0)
    fprintf(stderr, "WARNING: Failed to reset buffers.\n");


  fprintf(stderr, "Reading samples in async mode...\n");
  while (!do_exit) {
    /* Set the frequency */
    r = rtlsdr_set_center_freq(dev, def_frequency);
    if (r < 0)
      fprintf(stderr, "WARNING: Failed to set center freq.\n");
    else
      fprintf(stderr, "Tuned to %u Hz.\n", rtlsdr_get_center_freq(dev));

    r = rtlsdr_read_async(dev, rtlsdr_callback, (void *) demod, DEFAULT_ASYNC_BUF_NUMBER, out_block_size);
    do_exit_async = 0;

  }

  if (do_exit)
    fprintf(stderr, "\nUser cancel, exiting...\n");
  else
    fprintf(stderr, "\nLibrary error %d, exiting...\n", r);

  if (demod->file && (demod->file != stdout))
    fclose(demod->file);


  if (demod->signal_grabber)
    free(demod->sg_buf);

  if (demod)
    free(demod);

  rtlsdr_close(dev);
  free(buffer);
out:
  return r >= 0 ? r : -r;
}
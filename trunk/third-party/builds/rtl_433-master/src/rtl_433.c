#include <errno.h>
#include <signal.h>
#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <time.h>


#include <unistd.h>

#include "rtl-sdr.h"

#define DEFAULT_SAMPLE_RATE     250000
#define DEFAULT_FREQUENCY       433920000
#define DEFAULT_HOP_TIME        (60*10)
#define DEFAULT_HOP_EVENTS      2
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
static int do_exit_async=0, frequencies=0, events=0;
uint32_t frequency[MAX_PROTOCOLS];
time_t rawtime_old;
int flag;
uint32_t samp_rate=DEFAULT_SAMPLE_RATE;
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
static unsigned int signal_end   = 0;
static unsigned int signal_pulse_data[4000][3] = {{0}};
static unsigned int signal_pulse_counter = 0;


/* Supported modulation types */
#define     OOK_PWM_D   1   /* Pulses are of the same length, the distance varies */
#define     OOK_PWM_P   2   /* The length of the pulses varies */


typedef struct {
    unsigned int    id;
    char            name[256];
    unsigned int    modulation;
    unsigned int    short_limit;
    unsigned int    long_limit;
    unsigned int    reset_limit;
    int     (*json_callback)(uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS]) ;
} r_device;

static int debug_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    int i,j,k;
    fprintf(stderr, "\n");
    for (i=0 ; i<BITBUF_ROWS ; i++) {
        fprintf(stderr, "[%02d] ",i);
        for (j=0 ; j<BITBUF_COLS ; j++) {
            fprintf(stderr, "%02x ", bb[i][j]);
        }
        fprintf(stderr, ": ");
        for (j=0 ; j<BITBUF_COLS ; j++) {
            for (k=7 ; k>=0 ; k--) {
                if (bb[i][j] & 1<<k)
                    fprintf(stderr, "1");
                else
                    fprintf(stderr, "0");
            }
            fprintf(stderr, " ");
        }
        fprintf(stderr, "\n");
    }
    fprintf(stderr, "\n");

    return 0;
}

static int silvercrest_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    /* FIXME validate the received message better */
    if (bb[1][0] == 0xF8 &&
        bb[2][0] == 0xF8 &&
        bb[3][0] == 0xF8 &&
        bb[4][0] == 0xF8 &&
        bb[1][1] == 0x4d &&
        bb[2][1] == 0x4d &&
        bb[3][1] == 0x4d &&
        bb[4][1] == 0x4d) {
        /* Pretty sure this is a Silvercrest remote */
        fprintf(stderr, "Remote button event:\n");
        fprintf(stderr, "model = Silvercrest\n");
        fprintf(stderr, "%02x %02x %02x %02x %02x\n",bb[1][0],bb[0][1],bb[0][2],bb[0][3],bb[0][4]);

        if (debug_output)
            debug_callback(bb);

        return 1;
    }
    return 0;
}

static int rubicson_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    int temperature_before_dec;
    int temperature_after_dec;
    int16_t temp;

    /* FIXME validate the received message better, figure out crc */
    if (bb[1][0] == bb[2][0] && bb[2][0] == bb[3][0] && bb[3][0] == bb[4][0] &&
        bb[4][0] == bb[5][0] && bb[5][0] == bb[6][0] && bb[6][0] == bb[7][0] && bb[7][0] == bb[8][0] &&
        bb[8][0] == bb[9][0] && (bb[5][0] != 0 && bb[5][1] != 0 && bb[5][2] != 0)) {

        /* Nible 3,4,5 contains 12 bits of temperature
         * The temerature is signed and scaled by 10 */
        temp = (int16_t)((uint16_t)(bb[0][1] << 12) | (bb[0][2] << 4));
        temp = temp >> 4;

        temperature_before_dec = abs(temp / 10);
        temperature_after_dec = abs(temp % 10);

        fprintf(stderr, "Sensor temperature event:\n");
        fprintf(stderr, "protocol       = Rubicson/Auriol\n");
        fprintf(stderr, "rid            = %x\n",bb[0][0]);
        fprintf(stderr, "temp           = %s%d.%d\n",temp<0?"-":"",temperature_before_dec, temperature_after_dec);
        fprintf(stderr, "%02x %02x %02x %02x %02x\n",bb[1][0],bb[0][1],bb[0][2],bb[0][3],bb[0][4]);

        if (debug_output)
            debug_callback(bb);

        return 1;
    }
    return 0;
}

static int prologue_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    int rid;

    int16_t temp2;

    /* FIXME validate the received message better */
    if (((bb[1][0]&0xF0) == 0x90 && (bb[2][0]&0xF0) == 0x90 && (bb[3][0]&0xF0) == 0x90 && (bb[4][0]&0xF0) == 0x90 &&
        (bb[5][0]&0xF0) == 0x90 && (bb[6][0]&0xF0) == 0x90) ||
        ((bb[1][0]&0xF0) == 0x50 && (bb[2][0]&0xF0) == 0x50 && (bb[3][0]&0xF0) == 0x50 && (bb[4][0]&0xF0) == 0x50)) {

        /* Prologue sensor */
        temp2 = (int16_t)((uint16_t)(bb[1][2] << 8) | (bb[1][3]&0xF0));
        temp2 = temp2 >> 4;
        fprintf(stderr, "Sensor temperature event:\n");
        fprintf(stderr, "protocol      = Prologue\n");
        fprintf(stderr, "button        = %d\n",bb[1][1]&0x04?1:0);
        fprintf(stderr, "battery       = %s\n",bb[1][1]&0x08?"Ok":"Low");
        fprintf(stderr, "temp          = %s%d.%d\n",temp2<0?"-":"",abs((int16_t)temp2/10),abs((int16_t)temp2%10));
        fprintf(stderr, "humidity      = %d\n", ((bb[1][3]&0x0F)<<4)|(bb[1][4]>>4));
        fprintf(stderr, "channel       = %d\n",(bb[1][1]&0x03)+1);
        fprintf(stderr, "id            = %d\n",(bb[1][0]&0xF0)>>4);
        rid = ((bb[1][0]&0x0F)<<4)|(bb[1][1]&0xF0)>>4;
        fprintf(stderr, "rid           = %d\n", rid);
        fprintf(stderr, "hrid          = %02x\n", rid);

        fprintf(stderr, "%02x %02x %02x %02x %02x\n",bb[1][0],bb[1][1],bb[1][2],bb[1][3],bb[1][4]);

        if (debug_output)
            debug_callback(bb);

        return 1;
    }
    return 0;
}

static int waveman_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    /* Two bits map to 2 states, 0 1 -> 0 and 1 1 -> 1 */
    int i;
    uint8_t nb[3] = {0};

    if (((bb[0][0]&0x55)==0x55) && ((bb[0][1]&0x55)==0x55) && ((bb[0][2]&0x55)==0x55) && ((bb[0][3]&0x55)==0x00)) {
        for (i=0 ; i<3 ; i++) {
            nb[i] |= ((bb[0][i]&0xC0)==0xC0) ? 0x00 : 0x01;
            nb[i] |= ((bb[0][i]&0x30)==0x30) ? 0x00 : 0x02;
            nb[i] |= ((bb[0][i]&0x0C)==0x0C) ? 0x00 : 0x04;
            nb[i] |= ((bb[0][i]&0x03)==0x03) ? 0x00 : 0x08;
        }

        fprintf(stderr, "Remote button event:\n");
        fprintf(stderr, "model   = Waveman Switch Transmitter\n");
        fprintf(stderr, "id      = %c\n", 'A'+nb[0]);
        fprintf(stderr, "channel = %d\n", (nb[1]>>2)+1);
        fprintf(stderr, "button  = %d\n", (nb[1]&3)+1);
        fprintf(stderr, "state   = %s\n", (nb[2]==0xe) ? "on" : "off");
        fprintf(stderr, "%02x %02x %02x\n",nb[0],nb[1],nb[2]);

        if (debug_output)
            debug_callback(bb);

        return 1;
    }
    return 0;
}

static int steffen_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {

    if (bb[0][0]==0x00 && ((bb[1][0]&0x07)==0x07) && bb[1][0]==bb[2][0] && bb[2][0]==bb[3][0]) {
        
        fprintf(stderr, "Remote button event:\n");
        fprintf(stderr, "model   = Steffan Switch Transmitter\n");
	fprintf(stderr, "code    = %d%d%d%d%d\n", (bb[1][0]&0x80)>>7, (bb[1][0]&0x40)>>6, (bb[1][0]&0x20)>>5, (bb[1][0]&0x10)>>4, (bb[1][0]&0x08)>>3);

	if ((bb[1][2]&0x0f)==0x0e)
            fprintf(stderr, "button  = A\n");
        else if ((bb[1][2]&0x0f)==0x0d)
            fprintf(stderr, "button  = B\n");
        else if ((bb[1][2]&0x0f)==0x0b)
            fprintf(stderr, "button  = C\n");
        else if ((bb[1][2]&0x0f)==0x07)
            fprintf(stderr, "button  = D\n");
        else if ((bb[1][2]&0x0f)==0x0f)
            fprintf(stderr, "button  = ALL\n");
	else
	    fprintf(stderr, "button  = unknown\n");

	if ((bb[1][2]&0xf0)==0xf0) {
            fprintf(stderr, "state   = OFF\n");
	} else {
            fprintf(stderr, "state   = ON\n");
        }

        if (debug_output)
            debug_callback(bb);

        return 1;
    }
    return 0;
}


uint16_t AD_POP(uint8_t bb[BITBUF_COLS], uint8_t bits, uint8_t bit) {
    uint16_t val = 0;
    uint8_t i, byte_no, bit_no;
    for (i=0;i<bits;i++) {
        byte_no=   (bit+i)/8 ;
        bit_no =7-((bit+i)%8);
        if (bb[byte_no]&(1<<bit_no)) val = val | (1<<i);
    }
    return val;
}

static int em1000_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    // based on fs20.c
    uint8_t dec[10];
    uint8_t bytes=0;
    uint8_t bit=18; // preamble
    uint8_t bb_p[14];
    char* types[] = {"S", "?", "GZ"};
    uint8_t checksum_calculated = 0;
    uint8_t i;
	uint8_t stopbit;
	uint8_t checksum_received;

    // check and combine the 3 repetitions
    for (i = 0; i < 14; i++) {
        if(bb[0][i]==bb[1][i] || bb[0][i]==bb[2][i]) bb_p[i]=bb[0][i];
        else if(bb[1][i]==bb[2][i])                  bb_p[i]=bb[1][i];
        else return 0;
    }

    // read 9 bytes with stopbit ...
    for (i = 0; i < 9; i++) {
        dec[i] = AD_POP (bb_p, 8, bit); bit+=8;
        stopbit=AD_POP (bb_p, 1, bit); bit+=1;
        if (!stopbit) {
//            fprintf(stderr, "!stopbit: %i\n", i);
            return 0;
        }
        checksum_calculated ^= dec[i];
        bytes++;
    }

    // Read checksum
    checksum_received = AD_POP (bb_p, 8, bit); bit+=8;
    if (checksum_received != checksum_calculated) {
//        fprintf(stderr, "checksum_received != checksum_calculated: %d %d\n", checksum_received, checksum_calculated);
        return 0;
    }

//for (i = 0; i < bytes; i++) fprintf(stderr, "%02X ", dec[i]); fprintf(stderr, "\n");

    // based on 15_CUL_EM.pm
    fprintf(stderr, "Energy sensor event:\n");
    fprintf(stderr, "protocol      = ELV EM 1000\n");
    fprintf(stderr, "type          = EM 1000-%s\n",dec[0]>=1&&dec[0]<=3?types[dec[0]-1]:"?");
    fprintf(stderr, "code          = %d\n",dec[1]);
    fprintf(stderr, "seqno         = %d\n",dec[2]);
    fprintf(stderr, "total cnt     = %d\n",dec[3]|dec[4]<<8);
    fprintf(stderr, "current cnt   = %d\n",dec[5]|dec[6]<<8);
    fprintf(stderr, "peak cnt      = %d\n",dec[7]|dec[8]<<8);

    return 1;
}

static int ws2000_callback(uint8_t bb[BITBUF_ROWS][BITBUF_COLS]) {
    // based on http://www.dc3yc.privat.t-online.de/protocol.htm
    uint8_t dec[13];
    uint8_t nibbles=0;
    uint8_t bit=11; // preamble
    char* types[]={"!AS3", "AS2000/ASH2000/S2000/S2001A/S2001IA/ASH2200/S300IA", "!S2000R", "!S2000W", "S2001I/S2001ID", "!S2500H", "!Pyrano", "!KS200/KS300"};
    uint8_t check_calculated=0, sum_calculated=0;
    uint8_t i;
    uint8_t stopbit;
	uint8_t sum_received;

    dec[0] = AD_POP (bb[0], 4, bit); bit+=4;
    stopbit= AD_POP (bb[0], 1, bit); bit+=1;
    if (!stopbit) {
//fprintf(stderr, "!stopbit\n");
        return 0;
    }
    check_calculated ^= dec[0];
    sum_calculated   += dec[0];

    // read nibbles with stopbit ...
    for (i = 1; i <= (dec[0]==4?12:8); i++) {
        dec[i] = AD_POP (bb[0], 4, bit); bit+=4;
        stopbit= AD_POP (bb[0], 1, bit); bit+=1;
        if (!stopbit) {
//fprintf(stderr, "!stopbit %i\n", i);
            return 0;
        }
        check_calculated ^= dec[i];
        sum_calculated   += dec[i];
        nibbles++;
    }

    if (check_calculated) {
//fprintf(stderr, "check_calculated (%d) != 0\n", check_calculated);
        return 0;
    }

    // Read sum
    sum_received = AD_POP (bb[0], 4, bit); bit+=4;
    sum_calculated+=5;
    sum_calculated&=0xF;
    if (sum_received != sum_calculated) {
//fprintf(stderr, "sum_received (%d) != sum_calculated (%d) ", sum_received, sum_calculated);
        return 0;
    }

//for (i = 0; i < nibbles; i++) fprintf(stderr, "%02X ", dec[i]); fprintf(stderr, "\n");

    fprintf(stderr, "Weather station sensor event:\n");
    fprintf(stderr, "protocol      = ELV WS 2000\n");
    fprintf(stderr, "type (!=ToDo) = %s\n", dec[0]<=7?types[dec[0]]:"?");
    fprintf(stderr, "code          = %d\n", dec[1]&7);
    fprintf(stderr, "temp          = %s%d.%d\n", dec[1]&8?"-":"", dec[4]*10+dec[3], dec[2]);
    fprintf(stderr, "humidity      = %d.%d\n", dec[7]*10+dec[6], dec[5]);
    if(dec[0]==4) {
        fprintf(stderr, "pressure      = %d\n", 200+dec[10]*100+dec[9]*10+dec[8]);
    }

    return 1;
}


// timings based on samp_rate=1024000
r_device rubicson = {
    /* .id             = */ 1,
    /* .name           = */ "Rubicson Temperature Sensor",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 1744/4,
    /* .long_limit     = */ 3500/4,
    /* .reset_limit    = */ 5000/4,
    /* .json_callback  = */ &rubicson_callback,
};

r_device prologue = {
    /* .id             = */ 2,
    /* .name           = */ "Prologue Temperature Sensor",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 3500/4,
    /* .long_limit     = */ 7000/4,
    /* .reset_limit    = */ 15000/4,
    /* .json_callback  = */ &prologue_callback,
};

r_device silvercrest = {
    /* .id             = */ 3,
    /* .name           = */ "Silvercrest Remote Control",
    /* .modulation     = */ OOK_PWM_P,
    /* .short_limit    = */ 600/4,
    /* .long_limit     = */ 5000/4,
    /* .reset_limit    = */ 15000/4,
    /* .json_callback  = */ &silvercrest_callback,
};

r_device tech_line_fws_500 = {
    /* .id             = */ 4,
    /* .name           = */ "Tech Line FWS-500 Sensor",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 3500/4,
    /* .long_limit     = */ 7000/4,
    /* .reset_limit    = */ 15000/4,
    // /* .json_callback  = */ &rubicson_callback,
};

r_device generic_hx2262 = {
    /* .id             = */ 5,
    /* .name           = */ "Window/Door sensor",
    /* .modulation     = */ OOK_PWM_P,
    /* .short_limit    = */ 1300/4,
    /* .long_limit     = */ 10000/4,
    /* .reset_limit    = */ 40000/4,
    // /* .json_callback  = */ &silvercrest_callback,
};

r_device technoline_ws9118 = {
    /* .id             = */ 6,
    /* .name           = */ "Technoline WS9118",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 1800/4,
    /* .long_limit     = */ 3500/4,
    /* .reset_limit    = */ 15000/4,
    /* .json_callback  = */ &debug_callback,
};


r_device elv_em1000 = {
    /* .id             = */ 7,
    /* .name           = */ "ELV EM 1000",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 750/4,
    /* .long_limit     = */ 7250/4,
    /* .reset_limit    = */ 30000/4,
    /* .json_callback  = */ &em1000_callback,
};

r_device elv_ws2000 = {
    /* .id             = */ 8,
    /* .name           = */ "ELV WS 2000",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ (602+(1155-602)/2)/4,
    /* .long_limit     = */ ((1755635-1655517)/2)/4, // no repetitions
    /* .reset_limit    = */ ((1755635-1655517)*2)/4,
    /* .json_callback  = */ &ws2000_callback,
};

r_device waveman = {
    /* .id             = */ 6,
    /* .name           = */ "Waveman Switch Transmitter",
    /* .modulation     = */ OOK_PWM_P,
    /* .short_limit    = */ 1000/4,
    /* .long_limit     = */ 8000/4,
    /* .reset_limit    = */ 30000/4,
    /* .json_callback  = */ &waveman_callback,
};

r_device steffen = {
    /* .id             = */ 9,
    /* .name           = */ "Steffen Switch Transmitter",
    /* .modulation     = */ OOK_PWM_D,
    /* .short_limit    = */ 140,
    /* .long_limit     = */ 270,
    /* .reset_limit    = */ 1500,
    /* .json_callback  = */ &steffen_callback,
};


struct protocol_state {
    int (*callback)(uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS]);

    /* bits state */
    int bits_col_idx;
    int bits_row_idx;
    int bits_bit_col_idx;
    uint8_t bits_buffer[BITBUF_ROWS][BITBUF_COLS];
    int16_t bits_per_row[BITBUF_ROWS];
    int     bit_rows;
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
    int16_t filter_buffer[MAXIMAL_BUF_LENGTH+FILTER_ORDER];
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

void usage(void)
{
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


static void sighandler(int signum)
{
    fprintf(stderr, "Signal caught, exiting!\n");
    do_exit = 1;
    rtlsdr_cancel_async(dev);
}


/* precalculate lookup table for envelope detection */
static void calc_squares() {
    int i;
    for (i=0 ; i<256 ; i++)
        scaled_squares[i] = (128-i) * (128-i);
}

/** This will give a noisy envelope of OOK/ASK signals
 *  Subtract the bias (-128) and get an envelope estimation
 *  The output will be written in the input buffer
 *  @returns   pointer to the input buffer
 */

static void envelope_detect(unsigned char *buf, uint32_t len, int decimate)
{
    uint16_t* sample_buffer = (uint16_t*) buf;
    unsigned int i;
    unsigned op = 0;
    unsigned int stride = 1<<decimate;

    for (i=0 ; i<len/2 ; i+=stride) {
        sample_buffer[op++] = scaled_squares[buf[2*i  ]]+scaled_squares[buf[2*i+1]];
    }
}

static void demod_reset_bits_packet(struct protocol_state* p) {
    memset(p->bits_buffer, 0 ,BITBUF_ROWS*BITBUF_COLS);
    memset(p->bits_per_row, 0 ,BITBUF_ROWS);
    p->bits_col_idx = 0;
    p->bits_bit_col_idx = 7;
    p->bits_row_idx = 0;
    p->bit_rows = 0;
}

static void demod_add_bit(struct protocol_state* p, int bit) {
    p->bits_buffer[p->bits_row_idx][p->bits_col_idx] |= bit<<p->bits_bit_col_idx;
    p->bits_bit_col_idx--;
    if (p->bits_bit_col_idx<0) {
        p->bits_bit_col_idx = 7;
        p->bits_col_idx++;
        if (p->bits_col_idx>BITBUF_COLS-1) {
            p->bits_col_idx = BITBUF_COLS-1;
//            fprintf(stderr, "p->bits_col_idx>%i!\n", BITBUF_COLS-1);
        }
    }
    p->bits_per_row[p->bit_rows]++;
}

static void demod_next_bits_packet(struct protocol_state* p) {
    p->bits_col_idx = 0;
    p->bits_row_idx++;
    p->bits_bit_col_idx = 7;
    if (p->bits_row_idx>BITBUF_ROWS-1) {
        p->bits_row_idx = BITBUF_ROWS-1;
        //fprintf(stderr, "p->bits_row_idx>%i!\n", BITBUF_ROWS-1);
    }
    p->bit_rows++;
    if (p->bit_rows > BITBUF_ROWS-1)
        p->bit_rows -=1;
}

static void demod_print_bits_packet(struct protocol_state* p) {
    int i,j,k;

    fprintf(stderr, "\n");
    for (i=0 ; i<p->bit_rows+1 ; i++) {
        fprintf(stderr, "[%02d] {%d} ",i, p->bits_per_row[i]);
        for (j=0 ; j<((p->bits_per_row[i]+8)/8) ; j++) {
	        fprintf(stderr, "%02x ", p->bits_buffer[i][j]);
        }
        fprintf(stderr, ": ");
        for (j=0 ; j<((p->bits_per_row[i]+8)/8) ; j++) {
            for (k=7 ; k>=0 ; k--) {
                if (p->bits_buffer[i][j] & 1<<k)
                    fprintf(stderr, "1");
                else
                    fprintf(stderr, "0");
            }
//            fprintf(stderr, "=0x%x ",demod->bits_buffer[i][j]);
            fprintf(stderr, " ");
        }
        fprintf(stderr, "\n");
    }
    fprintf(stderr, "\n");
    return;
}

static void register_protocol(struct dm_state *demod, r_device *t_dev) {
    struct protocol_state *p =  calloc(1,sizeof(struct protocol_state));
    p->short_limit  = (float)t_dev->short_limit/((float)DEFAULT_SAMPLE_RATE/(float)samp_rate);
    p->long_limit   = (float)t_dev->long_limit /((float)DEFAULT_SAMPLE_RATE/(float)samp_rate);
    p->reset_limit  = (float)t_dev->reset_limit/((float)DEFAULT_SAMPLE_RATE/(float)samp_rate);
    p->modulation   = t_dev->modulation;
    p->callback     = t_dev->json_callback;
    demod_reset_bits_packet(p);

    demod->r_devs[demod->r_dev_num] = p;
    demod->r_dev_num++;

    fprintf(stderr, "Registering protocol[%02d] %s\n",demod->r_dev_num, t_dev->name);

    if (demod->r_dev_num > MAX_PROTOCOLS)
        fprintf(stderr, "Max number of protocols reached %d\n",MAX_PROTOCOLS);
}



static void pwm_analyze(struct dm_state *demod, int16_t *buf, uint32_t len)
{
    unsigned int i;

    for (i=0 ; i<len ; i++) {
        if (buf[i] > demod->level_limit) {
            if (!signal_start)
                signal_start = counter;
            if (print) {
                pulses_found++;
                pulse_start = counter;
                signal_pulse_data[signal_pulse_counter][0] = counter;
                signal_pulse_data[signal_pulse_counter][1] = -1;
                signal_pulse_data[signal_pulse_counter][2] = -1;
                if (debug_output) fprintf(stderr, "pulse_distance %d\n",counter-pulse_end);
                if (debug_output) fprintf(stderr, "pulse_start distance %d\n",pulse_start-prev_pulse_start);
                if (debug_output) fprintf(stderr, "pulse_start[%d] found at sample %d, value = %d\n",pulses_found, counter, buf[i]);
                prev_pulse_start = pulse_start;
                print =0;
                print2 = 1;
            }
        }
        counter++;
        if (buf[i] < demod->level_limit) {
            if (print2) {
                pulse_avg += counter-pulse_start;
                if (debug_output) fprintf(stderr, "pulse_end  [%d] found at sample %d, pulse length = %d, pulse avg length = %d\n",
                        pulses_found, counter, counter-pulse_start, pulse_avg/pulses_found);
                pulse_end = counter;
                print2 = 0;
                signal_pulse_data[signal_pulse_counter][1] = counter;
                signal_pulse_data[signal_pulse_counter][2] = counter-pulse_start;
                signal_pulse_counter++;
                if (signal_pulse_counter >= 4000) {
                    signal_pulse_counter = 0;
                    goto err;
                }
            }
            print = 1;
            if (signal_start && (pulse_end + 20000 < counter)) {
                signal_end = counter - 40000;
                fprintf(stderr, "*** signal_start = %d, signal_end = %d\n",signal_start-10000, signal_end);
                fprintf(stderr, "signal_len = %d,  pulses = %d\n", signal_end-(signal_start-10000), pulses_found);
                pulses_found = 0;

                signal_pulse_counter = 0;
                
                signal_start = 0;
            }
        }


    }
    return;

err:
    fprintf(stderr, "To many pulses detected, probably bad input data or input parameters\n");
    return;
}

/* The distance between pulses decodes into bits */

static void pwm_d_decode(struct dm_state *demod, struct protocol_state* p, int16_t *buf, uint32_t len) {
    unsigned int i;

    for (i=0 ; i<len ; i++) {
        if (buf[i] > demod->level_limit) {
            p->pulse_count = 1;
            p->start_c = 1;
        }
        if (p->pulse_count && (buf[i] < demod->level_limit)) {
            p->pulse_length = 0;
            p->pulse_distance = 1;
            p->sample_counter = 0;
            p->pulse_count = 0;
        }
        if (p->start_c) p->sample_counter++;
        if (p->pulse_distance && (buf[i] > demod->level_limit)) {
            if (p->sample_counter < p->short_limit) {
                demod_add_bit(p, 0);
            } else if (p->sample_counter < p->long_limit) {
                demod_add_bit(p, 1);
            } else {
                demod_next_bits_packet(p);
                p->pulse_count    = 0;
                p->sample_counter = 0;
            }
            p->pulse_distance = 0;
        }
        if (p->sample_counter > p->reset_limit) {
            p->start_c    = 0;
            p->sample_counter = 0;
            p->pulse_distance = 0;
            if (p->callback)
                events+=p->callback(p->bits_buffer);
            else
                demod_print_bits_packet(p);

            demod_reset_bits_packet(p);
        }
    }
}

/* The length of pulses decodes into bits */

static void pwm_p_decode(struct dm_state *demod, struct protocol_state* p, int16_t *buf, uint32_t len) {
    unsigned int i;

    for (i=0 ; i<len ; i++) {
        if (buf[i] > demod->level_limit && !p->start_bit) {
            /* start bit detected */
            p->start_bit      = 1;
            p->start_c        = 1;
            p->sample_counter = 0;
//            fprintf(stderr, "start bit pulse start detected\n");
        }

        if (!p->real_bits && p->start_bit && (buf[i] < demod->level_limit)) {
            /* end of startbit */
            p->real_bits = 1;
//            fprintf(stderr, "start bit pulse end detected\n");
        }
        if (p->start_c) p->sample_counter++;


        if (!p->pulse_start && p->real_bits && (buf[i] > demod->level_limit)) {
            /* save the pulse start, it will never be zero */
            p->pulse_start = p->sample_counter;
//           fprintf(stderr, "real bit pulse start detected\n");

        }

        if (p->real_bits && p->pulse_start && (buf[i] < demod->level_limit)) {
            /* end of pulse */

            p->pulse_length = p->sample_counter-p->pulse_start;
//           fprintf(stderr, "real bit pulse end detected %d\n", p->pulse_length);
//           fprintf(stderr, "space duration %d\n", p->sample_counter);

            if (p->pulse_length <= p->short_limit) {
                demod_add_bit(p, 1);
            } else if (p->pulse_length > p->short_limit) {
                demod_add_bit(p, 0);
            }
            p->sample_counter = 0;
            p->pulse_start    = 0;
        }

        if (p->real_bits && p->sample_counter > p->long_limit) {
            demod_next_bits_packet(p);

            p->start_bit = 0;
            p->real_bits = 0;
        }

        if (p->sample_counter > p->reset_limit) {
            p->start_c = 0;
            p->sample_counter = 0;
            //demod_print_bits_packet(p);
            if (p->callback)
                events+=p->callback(p->bits_buffer);
            else
                demod_print_bits_packet(p);
            demod_reset_bits_packet(p);

            p->start_bit = 0;
            p->real_bits = 0;
        }
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

static uint16_t lp_xmem[FILTER_ORDER] = {0};

#define F_SCALE 15
#define S_CONST (1<<F_SCALE)
#define FIX(x) ((int)(x*S_CONST))

int a[FILTER_ORDER+1] = {FIX(1.00000),FIX(0.96907)};
int b[FILTER_ORDER+1] = {FIX(0.015466),FIX(0.015466)};

static void low_pass_filter(uint16_t *x_buf, int16_t *y_buf, uint32_t len)
{
    unsigned int i;

    /* Calculate first sample */
    y_buf[0] = ((a[1]*y_buf[-1]>>1) + (b[0]*x_buf[0]>>1) + (b[1]*lp_xmem[0]>>1)) >> (F_SCALE-1);
    for (i=1 ; i<len ; i++) {
        y_buf[i] = ((a[1]*y_buf[i-1]>>1) + (b[0]*x_buf[i]>>1) + (b[1]*x_buf[i-1]>>1)) >> (F_SCALE-1);
    }

    /* Save last sample */
    memcpy(lp_xmem, &x_buf[len-1-FILTER_ORDER], FILTER_ORDER*sizeof(int16_t));
    memcpy(&y_buf[-FILTER_ORDER], &y_buf[len-1-FILTER_ORDER], FILTER_ORDER*sizeof(int16_t));
    //fprintf(stderr, "%d\n", y_buf[0]);
}


static void rtlsdr_callback(unsigned char *buf, uint32_t len, void *ctx)
{
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
            low_pass_filter(sbuf, demod->f_buf, len>>(demod->decimation_level+1));
        }

        if (demod->analyze) {
            pwm_analyze(demod, demod->f_buf, len/2);
        }

        if (bytes_to_read > 0)
            bytes_to_read -= len;

    }
}

int main(int argc, char **argv)
{

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
    int frequency_current=0;
    uint32_t out_block_size = DEFAULT_BUF_LENGTH;
    int device_count;
    char vendor[256], product[256], serial[256];

    demod = malloc(sizeof(struct dm_state));
    memset(demod,0,sizeof(struct dm_state));

    /* initialize tables */
    calc_squares();

    demod->f_buf = &demod->filter_buffer[FILTER_ORDER];
    demod->decimation_level = DEFAULT_DECIMATION_LEVEL;
    demod->level_limit      = DEFAULT_LEVEL_LIMIT;


    while ((opt = getopt(argc, argv, "x:z:p:Dtam:r:c:l:d:f:g:s:b:n:S::")) != -1) {
        switch (opt) {
        case 'd':
            dev_index = atoi(optarg);
            break;
        case 'f':
            if(frequencies<MAX_PROTOCOLS) frequency[frequencies++] = (uint32_t)atof(optarg);
            else fprintf(stderr, "Max number of frequencies reached %d\n",MAX_PROTOCOLS);
            break;
        case 'g':
            gain = (int)(atof(optarg) * 10); /* tenths of a dB */
            break;
        case 'p':
            ppm_error = atoi(optarg);
            break;
        case 's':
            samp_rate = (uint32_t)atof(optarg);
            break;
        case 'b':
            out_block_size = (uint32_t)atof(optarg);
            break;
        case 'l':
            demod->level_limit = (uint32_t)atof(optarg);
            break;
        case 'n':
            bytes_to_read = (uint32_t)atof(optarg) * 2;
            break;
        case 'c':
            demod->decimation_level = (uint32_t)atof(optarg);
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


    if (argc <= optind-1) {
        usage();
    } else {
        filename = argv[optind];
    }

    if(out_block_size < MINIMAL_BUF_LENGTH ||
       out_block_size > MAXIMAL_BUF_LENGTH ){
        fprintf(stderr,
            "Output block size wrong value, falling back to default\n");
        fprintf(stderr,
            "Minimal length: %u\n", MINIMAL_BUF_LENGTH);
        fprintf(stderr,
            "Maximal length: %u\n", MAXIMAL_BUF_LENGTH);
        out_block_size = DEFAULT_BUF_LENGTH;
    }

    buffer = malloc(out_block_size * sizeof(uint8_t));

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
    if (r < 0)
        fprintf(stderr, "WARNING: Failed to set sample rate.\n");
    else
        fprintf(stderr, "Sample rate set to %d.\n", rtlsdr_get_sample_rate(dev)); // Unfortunately, doesn't return real rate

    fprintf(stderr, "Sample rate decimation set to %d. %d->%d\n",demod->decimation_level, samp_rate, samp_rate>>demod->decimation_level);
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
            fprintf(stderr, "Tuner gain set to %f dB.\n", gain/10.0);
    }

    r = rtlsdr_set_freq_correction(dev, ppm_error);

    demod->save_data = 0;



    /* Reset endpoint before we start reading from it (mandatory) */
    r = rtlsdr_reset_buffer(dev);
    if (r < 0)
        fprintf(stderr, "WARNING: Failed to reset buffers.\n");


      frequency[0] = DEFAULT_FREQUENCY;
      frequencies=1;

    fprintf(stderr, "Reading samples in async mode...\n");
    while(!do_exit) {
        /* Set the frequency */
        r = rtlsdr_set_center_freq(dev, frequency[frequency_current]);
        if (r < 0)
            fprintf(stderr, "WARNING: Failed to set center freq.\n");
        else
            fprintf(stderr, "Tuned to %u Hz.\n", rtlsdr_get_center_freq(dev));
        r = rtlsdr_read_async(dev, rtlsdr_callback, (void *)demod,
                      DEFAULT_ASYNC_BUF_NUMBER, out_block_size);
        do_exit_async=0;
        frequency_current++;
        if(frequency_current>frequencies-1) frequency_current=0;
    }

    if (do_exit)
        fprintf(stderr, "\nUser cancel, exiting...\n");
    else
        fprintf(stderr, "\nLibrary error %d, exiting...\n", r);

    if (demod->file && (demod->file != stdout))
        fclose(demod->file);

    for (i=0 ; i<demod->r_dev_num ; i++)
        free(demod->r_devs[i]);

    if (demod->signal_grabber)
        free(demod->sg_buf);

    if(demod)
        free(demod);

    rtlsdr_close(dev);
    free (buffer);
out:
    return r >= 0 ? r : -r;
}
INCLUDES = $(all_includes) -I$(top_srcdir)/include
AM_CFLAGS = ${CFLAGS} -fPIC ${SYMBOL_VISIBILITY}

bin_PROGRAMS         = rtl_433

rtl_433_SOURCES      = rtl_433.c

rtl_433_LDADD        = $(LIBRTLSDR) $(LIBM)

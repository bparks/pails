PREFIX=/usr/local
LIBPREFIX=$(PREFIX)/lib

all :
	#Nothing to do; run make install

install :
	cp -r . $(LIBPREFIX)/pails
	ln -sf $(LIBPREFIX)/pails/tools/pails $(PREFIX)/bin/pails

uninstall :
	rm -rf $(LIBPREFIX)/pails
	rm -f $(PREFIX)/bin/pails
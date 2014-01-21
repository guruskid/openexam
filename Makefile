PACKAGE_NAME    = openexam-php
PACKAGE_VERSION = 1.0
PACKAGE_ADDRESS = anders.lovgren@bmc.uu.se
PACKAGE_COPYING = "Computing Department at BMC, Uppsala Biomedical Centre, Uppsala University"

SOURCES  = $(shell find . -type f -name '*.inc' -o -name '*.php' -o -name '*.menu' -o -name '*.ui')

XGETTEXT = xgettext
MSGMERGE = msgmerge
MSGFMT   = msgfmt

XGETTEXT_OPTIONS = --language=PHP --package-name=$(PACKAGE_NAME) --package-version=$(PACKAGE_VERSION) --msgid-bugs-address=$(PACKAGE_ADDRESS) --copyright-holder=$(PACKAGE_COPYING) --add-comments='//{tr}' --from-code=ISO-8859-1 --no-wrap --output=$(GETTEXT_POTFILE)
MSGMERGE_OPTIONS = --update --no-wrap
MSGFMT_OPTIONS   = --statistics --check --output-file=$@

GETTEXT_POTFILE = locale/messages.pot
GETTEXT_POFILES = $(shell find locale -type f -name *.po)
GETTEXT_MOFILES = $(GETTEXT_POFILES:.po=.mo)

.PHONY : all clean all-clean distclean install gettext gettext-update gettext-merge gettext-compile

all : gettext
gettext : gettext-update gettext-merge gettext-compile

gettext-update : $(GETTEXT_POTFILE)
$(GETTEXT_POTFILE) : $(SOURCES)
	$(XGETTEXT) $(XGETTEXT_OPTIONS) $(SOURCES)
gettext-merge : $(GETTEXT_POFILES)
$(GETTEXT_POFILES) : $(GETTEXT_POTFILE)
	$(MSGMERGE) $(MSGMERGE_OPTIONS) $@ $? && touch $@
gettext-compile : gettext-merge $(GETTEXT_MOFILES)

%.mo : %.po
	$(MSGFMT) $(MSGFMT_OPTIONS) $+

install :
	@(echo "Sorry, no install exists. See file INSTALL")

clean :
	rm -f $(GETTEXT_MOFILES)
all-clean : clean
	find . | grep '~' | xargs rm -f
distclean : all-clean
VERSION = "1.0.3"
MVERSION = "1.0.0"
PACKAGE = jofavorites
ZIPFILE = $(PACKAGE)-$(VERSION).zip
MZIPFILE = $(PACKAGE)-$(MVERSION).zip
UPDATEFILE = $(PACKAGE)-update.xml
MKFILE_PATH := $(abspath $(lastword $(MAKEFILE_LIST)))
MKFILE_DIR := $(dir $(MKFILE_PATH))
ROOT = $(shell pwd)
PACKAGES = $(ROOT)/packages




.PHONY: $(ZIPFILE)

ALL : $(ZIPFILE) fixsha $(MZIPFILE)



ZIPIGNORES = -x "*.git*" -x "*.svn*" -x "thumbs/*" -x "*.zip" -x "tests/*" -x Makefile -x "*.sh" -x "*/*/*.git*" 



$(ZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/joomla; zip -r ../$@ * $(ZIPIGNORES))

$(MZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/Mediawiki; zip -r ../$@ * $(ZIPIGNORES))


fixversions:
	@echo "Updating all install xml files to version $(VERSION)"
	@find . \( -name '*.xml' ! -name 'default.xml' ! -name 'metadata.xml' ! -name 'config.xml' \) -exec  ./fixvd.sh {} $(VERSION) \;

revertversions:
	@echo "Reverting all install xml files"
	@find . \( -name '*.xml' ! -name 'default.xml' ! -name 'metadata.xml' ! -name 'config.xml' \) -exec git checkout {} \;

fixsha:
	@echo "Updating update xml files with checksums"
	@(cd $(ROOT);./fixsha.sh $(ZIPFILE) $(UPDATEFILE))

untabify:
	@find . -name '*.php' -exec $(MKFILE_DIR)/replacetabs.sh {} \;




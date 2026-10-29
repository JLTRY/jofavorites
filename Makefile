VERSION = "1.0.5"
MEDIAWIKIVERSION = "1.0.2"
DRUPALVERSION = "1.0.2"
WORDPRESSVERSION = "1.0.2"
PACKAGE = jofavorites
ZIPFILE = $(PACKAGE)-$(VERSION).zip
MEDIAWIKIZIPFILE = mediawiki$(PACKAGE)-$(MEDIAWIKIVERSION).zip
DRUPALZIPFILE = shortcode_favorites-$(DRUPALVERSION).zip
WORDPRESSZIPFILE = wordpress_favorites-$(WORDPRESSVERSION).zip
UPDATEFILE = joomla/$(PACKAGE)-update.xml
MKFILE_PATH := $(abspath $(lastword $(MAKEFILE_LIST)))
MKFILE_DIR := $(dir $(MKFILE_PATH))
ROOT = $(shell pwd)
PACKAGES = $(ROOT)/packages




.PHONY: $(ZIPFILE)

ALL : $(ZIPFILE) fixsha $(MEDIAWIKIZIPFILE) $(DRUPALZIPFILE) $(WORDPRESSZIPFILE)



ZIPIGNORES = -x "*.git*" -x "*.svn*" -x "thumbs/*" -x "*.zip" -x "tests/*" -x Makefile -x "*.sh" -x "*/*/*.git*" 



$(ZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/joomla; zip -r ../$@ * $(ZIPIGNORES))

$(MEDIAWIKIZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/Mediawiki; zip -r ../$@ * $(ZIPIGNORES))

$(DRUPALZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/drupal; zip -r ../$@ * $(ZIPIGNORES))

$(WORDPRESSZIPFILE): 
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $@"
	@rm -f ../$@
	@(cd $(ROOT)/wordpress; zip -r ../$@ * $(ZIPIGNORES))



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




VERSION = "1.0.6"
MEDIAWIKIVERSION = "1.0.2"
DRUPALVERSION = "1.0.2"
WORDPRESSVERSION = "1.0.2"
PACKAGE = pkg_jofavorites
ZIPFILE = $(PACKAGE)-$(VERSION).zip
MEDIAWIKIZIPFILE = mediawiki_jofavorites-$(MEDIAWIKIVERSION).zip
DRUPALZIPFILE = shortcode_favorites-$(DRUPALVERSION).zip
WORDPRESSZIPFILE = wordpress_favorites-$(WORDPRESSVERSION).zip
UPDATEFILE = $(PACKAGE)/update_pkg.xml
MKFILE_PATH := $(abspath $(lastword $(MAKEFILE_LIST)))
MKFILE_DIR := $(dir $(MKFILE_PATH))
ROOT = $(shell pwd)
PACKAGES = $(ROOT)/packages




.PHONY: $(ZIPFILE)


ZIPIGNORES = -x "*.git*" -x "*.svn*" -x "thumbs/*" -x "*.zip" -x "tests/*" -x Makefile -x "*.sh" -x "*/*/*.git*" 

INSTALLS = jofavorites libfavorites
NAMES = $(INSTALLS) $(EXTRAS)

ZIPS = $(NAMES:=.zip)

parts: $(ZIPS)

ALL : parts $(ZIPFILE) fixsha $(MEDIAWIKIZIPFILE) $(DRUPALZIPFILE) $(WORDPRESSZIPFILE)

%.zip:
	@echo "-------------------------------------------------------"
	@echo "Creating zip file for: $*"
	@rm -f $@
	@(cd $*; zip -r ../$@ * $(ZIPIGNORES))

$(ZIPFILE): $(ZIPS)
	@echo "-------------------------------------------------------"
	@echo "Creating extension zip file: $(ZIPFILE)"
	@mv $(INSTALLS:=.zip) $(PACKAGE)/packages/
	@(cd $(PACKAGE); zip -r ../$@ * $(ZIPIGNORES))
	@echo "-------------------------------------------------------"
	@echo "Finished creating package $(ZIPFILE)."


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




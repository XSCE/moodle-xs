# install root
DESTDIR=/

$(DESTDIR):
	mkdir -p $(DESTDIR)

# For developers:


# symbols
PKGNAME = moodle-xs
VERSION =$(shell git describe | sed 's/^v//' | sed 's/-/./g')
RELEASE = 1
COMMITID = $(shell git rev-parse HEAD)
ARCH = noarch

# NOTE: Release is hardcoded in the spec file to 1
NV = $(PKGNAME)-$(VERSION)
NVR = $(NV)-$(RELEASE)
DISTVER=xs11

# rpm target directory
BUILDDIR = $(PWD)/build
TARBALL    = $(BUILDDIR)/SOURCES/$(NV).tar.gz
SRPM       = $(BUILDDIR)/SRPMS/$(NVR).$(DISTVER).src.rpm
RPM        = $(BUILDDIR)/RPMS/$(ARCH)/$(NVR).$(DISTVER).$(ARCH).rpm

RPMBUILD = rpmbuild \
	--define "_topdir $(BUILDDIR)" \
         --define "dist .$(DISTVER)"

SOURCES: $(TARBALL)
$(TARBALL):
	mkdir -p $(BUILDDIR)/BUILD $(BUILDDIR)/RPMS \
	$(BUILDDIR)/SOURCES $(BUILDDIR)/SPECS $(BUILDDIR)/SRPMS
	mkdir -p $(NV)
	git archive --format=tar --prefix="$(NV)/" HEAD > $(NV).tar
	mkdir -p $(NV)
	echo $(VERSION) > $(NV)/build-version
	tar -rf $(NV).tar $(NV)/build-version
	rm -fr $(NV)
	gzip $(NV).tar
	mv $(NV).tar.gz $(BUILDDIR)/SOURCES/

SRPM: $(SRPM)
$(SRPM): moodle-xs.spec SOURCES
	$(RPMBUILD) -bs --nodeps $(PKGNAME).spec

moodle-xs.spec: rpm/moodle-xs.spec.in
	sed -e 's:@PKGNAME@:$(PKGNAME):g' \
	    -e 's:@VERSION@:$(VERSION):g' \
	    -e 's:@RELEASE@:$(RELEASE):g' \
	    -e 's:@COMMITID@:$(COMMITID):g' \
		< $< > $@

RPM: $(RPM)
$(RPM): SRPM
	$(RPMBUILD) --rebuild $(SRPM)
	rm -fr $(BUILDDIR)/BUILD/$(NV)
	# Tolerate rpmlint errors
	rpmlint $(RPM) || echo "rpmlint errored out but we love you anyway"

publish: SOURCES SRPM
	rsync -e ssh --progress  $(RPM) \
	    xs-dev.laptop.org:/xsrepos/testing/olpc/11/i586/
	rsync -e ssh --progress $(SRPM) \
	    xs-dev.laptop.org:/xsrepos/testing/olpc/11/source/SRPMS/
	rsync -e ssh --progress $(TARBALL) \
	    xs-dev.laptop.org:/xsrepos/testing/olpc/11/source/SOURCES/
	ssh xs-dev.laptop.org sudo createrepo /xsrepos/testing/olpc/11/i586

# install: install target handled inside the spec file

# This forces a rebuild of xs-rsync.spec.in
.PHONY: moodle-xs.spec

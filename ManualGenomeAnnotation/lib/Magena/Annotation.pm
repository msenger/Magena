#-----------------------------------------------------------------
# Magena::Annotation
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# A light-weight wrapper around BioPerl's SeqFeature object, with few
# additional convenient methods.
# -----------------------------------------------------------------

package Magena::Annotation;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use base qw( Commons::Base Exporter );

# this must be here because the super-class has its own import()
sub import {
    shift->export_to_level (1, @_);
}

# (exported) constants used as GFF non-standard attributes
use constant {
    ATTR_TAXON    => 'taxon',
    ATTR_CREATED  => 'created',
    ATTR_MODIFIED => 'modified',
    ATTR_CURATOR  => 'curator',
};
our @EXPORT = qw( ATTR_TAXON ATTR_CREATED ATTR_MODIFIED ATTR_CURATOR );

#-----------------------------------------------------------------
# set/get "tags" (or "attributes"); $value is a refhash; it always adds
# given attributes (rather than fully replacing them)
# -----------------------------------------------------------------
sub attributes {
    my ($self, $value) = @_;
    if ($value) {
	throw ('An argument to ' . __PACKAGE__ . '->attributes should be a refhash')
	    unless ref ($value) eq 'HASH';
	$self->{attributes} = {} unless $self->{attributes};
	foreach my $key (keys %$value) {
	    $self->{attributes}->{$key} = $value->{$key};
	}
    } else {
	return ($self->{attributes} or {});
    }
}

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 id     => undef,
	 source => undef,
	 type   => undef,
	 start  => { type => Commons::Base->INTEGER },
	 end    => { type => Commons::Base->INTEGER },
	 score  => { type => Commons::Base->FLOAT },
	 strand => undef,
	 phase  => { type => Commons::Base->INTEGER },
	 );

    sub _accessible {
	my ($self, $attr) = @_;
	exists $_allowed{$attr} or $self->SUPER::_accessible ($attr);
    }
    sub _attr_prop {
	my ($self, $attr_name, $prop_name) = @_;
	my $attr = $_allowed {$attr_name};
	return ref ($attr) ? $attr->{$prop_name} : $attr if $attr;
	return $self->SUPER::_attr_prop ($attr_name, $prop_name);
    }
}

1;
__END__

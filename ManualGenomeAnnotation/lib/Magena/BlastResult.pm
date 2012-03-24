#-----------------------------------------------------------------
# Magena::BlastResult
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# A container for a light-weight object for a blast result for one
# query sequence.
#-----------------------------------------------------------------

package Magena::BlastResult;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use base qw( Commons::Base );
use Magena::BlastHit;

#-----------------------------------------------------------------
# Return a reference to raw data of this result. Or undef, if the
# reference does not exist.
# -----------------------------------------------------------------
sub raw_ref {
    my ($self, $value) = @_;
    $self->{raw_ref} = $value and return
	if defined $value;
    $self->{parent_obj}->_assign_raw_results
	unless defined $self->{raw_ref};
    return $self->{raw_ref} if -e $self->{raw_ref};
    return undef;
}

#-----------------------------------------------------------------
# Return a raw data of this result. Or undef, if the raw data do not
# exist.
# -----------------------------------------------------------------
sub raw {
    my ($self) = @_;
    my $raw_ref = $self->raw_ref;
    return undef unless $raw_ref;
    {
	local *FILE;
	if (open (FILE, $raw_ref)) {
	    local $/ = undef;
	    return <FILE>;
	}
    }
    return undef;
}

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 id          => undef,
	 description => undef,
	 length      => { type => Commons::Base->INTEGER },
	 hits        => { type     => 'Magena::BlastHit',
			  is_array => 1 },
#	 raw         => { readonly => 1 },  # has its own method
#	 raw_ref     => undef,              # has its own method
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

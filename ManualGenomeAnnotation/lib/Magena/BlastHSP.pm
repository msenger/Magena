#-----------------------------------------------------------------
# Magena::BlastHSP
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# A container for a light-weight object for a blast HSP.
#-----------------------------------------------------------------

package Magena::BlastHSP;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use parent qw( Commons::Base );

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 algorithm      => undef,
	 frac_identical => undef,
	 frac_conserved => undef,
	 gaps           => undef,
	 rank           => undef,
	 evalue         => { type => Commons::Base->FLOAT },
	 query_seq      => undef,
	 query_length   => { type => Commons::Base->INTEGER },
	 query_start    => { type => Commons::Base->INTEGER },
	 query_end      => { type => Commons::Base->INTEGER },
	 hit_seq        => undef,
	 hit_length     => { type => Commons::Base->INTEGER },
	 hit_start      => { type => Commons::Base->INTEGER },
	 hit_end        => { type => Commons::Base->INTEGER },
	 homology_seq   => undef,
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

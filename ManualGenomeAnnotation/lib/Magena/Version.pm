#-----------------------------------------------------------------
# Magena::Version
# Author: Martin Senger <martin.senger@gmail.com>
#-----------------------------------------------------------------

package Magena::Version;

use strict;
use warnings;

use version; our $VERSION = '0.2.1';   # v0.2.1

1;
__END__

=head1 NAME

Magena::Version - provide versioning for most of its modules

=head1 SYNOPSIS

  package Magena::Whatever;
  use Magena::Version;
  our $VERSION = $Magena::Version::VERSION;

=head1 DESCRIPTION

Use it to get a centralized version for the whole project.

=head1 DISCLAIMER

This software is provided "as is" without warranty of any kind.

=cut

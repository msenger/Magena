#-----------------------------------------------------------------
# Magena::Utilities
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
#-----------------------------------------------------------------

package Magena::Utilities;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;

#-----------------------------------------------------------------
# Return a reference to a list with fully qualified file names of the
# split results.
# -----------------------------------------------------------------
sub split_blast_results {
    shift;  # TBD better?
    return Magena::Utilities::blastsplit->new (@_)->split;
}

#-----------------------------------------------------------------
# Merge together file names from arguments (of the given object $obj)
# containing file names ('file' and 'files').
# -----------------------------------------------------------------
sub _files {
    my ($self, $obj) = @_;
    my @files = ();
    foreach my $file ($obj->file, $obj->files ? @{ $obj->files } : ()) {
	next unless $file;
	push (@files, $file);
    }
    return @files;
}

#-----------------------------------------------------------------
# An internal module.
#-----------------------------------------------------------------
package Magena::Utilities::blastsplit;
use base qw( Commons::Base );
use File::Spec;
use File::Basename;

#
sub split {
    my $self = shift;

    # input arguments
    my @files = Magena::Utilities->_files ($self);
    die "Missing argument 'file(s)'. Cannot split if not known what to split.\n"
	unless @files > 0;
    $self->output_dir ($files[0] . '.parts')
	unless $self->output_dir;
    $self->max (10)
	unless $self->max and $self->max > 0;
    die "Cannot use '" . $self->output_dir . "' as a directory. Such name already exists as a file.\n"
	if -e $self->output_dir and not -d $self->output_dir;

    # prepare output directory
    mkdir $self->output_dir
	unless -e  $self->output_dir;
    $self->{file_counter} = 1;
    $self->{doc_counter} = 0;
    $self->{output_files} = [];

    # read and split
    foreach my $input_file (@files) {
	my ($basename, $path, $suffix) = fileparse ($input_file, qr/\.[^.]*/);
	$self->{current_file_basename} = $basename;
	$self->{current_file_suffix} = ($suffix or '');
	my $document;
	local *INPUT;
	open (INPUT, '<', $input_file)
	    or die "Cannot open file: " . $input_file . ": $!\n";
	while (<INPUT>) {
	    if ( /^((?:\S+?)?BLAST[NPX]?)\s+(.+)$/i  # NCBI BLAST, PSIBLAST, RPSBLAST, MEGABLAST
		 || /^(P?GENEWISE|HFRAME|SWN|TSWN)\s+(.+)/i ) { # Paracel BTK
		$self->_write ($document) if $document;
		$document = $_;
	    } else {
		$document .= $_;
	    }
	}
	$self->_write ($document);   # flush the last document
	$self->_write (undef);       # and close the last output
	close INPUT;
    }
    return $self->{output_files};
}

# add given $document to an output file, or add to a new file if the
# current output has already enough documents; if the $document is
# undef and the last output file is still open, close it; store names
# of all output files
sub _write {
    my ($self, $document) = @_;
    unless ($document) {
	$self->_close;
	return;
    }
    if ($self->{doc_counter} >= $self->max or not $self->{ofh}) {
	$self->_close;
	my $output_file =
	    File::Spec->catfile ($self->output_dir,
				 $self->{current_file_basename} .
				 sprintf (".%05d%s",
					  $self->{file_counter}++,
					  $self->{current_file_suffix}));
	open ($self->{ofh}, '>', $output_file)
	    or die "Cannot create output file $output_file: $!\n";
	push (@{ $self->{output_files} }, $output_file);
	$self->{doc_counter} = 0;
    }
    print { $self->{ofh} } $document;
    $self->{doc_counter}++;
}
#
sub _close {
    my $self = shift;
    close $self->{ofh} or
	die "Cannot close output file: $!\n"
	if $self->{ofh};
    undef $self->{ofh};
}

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 file       => undef,
	 files      => { is_array => 1 },
	 output_dir => undef,
	 max        => { type => Commons::Base->INTEGER },
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

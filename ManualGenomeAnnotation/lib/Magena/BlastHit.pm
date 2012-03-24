#-----------------------------------------------------------------
# Magena::BlastHit
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# A container for a light-weight object for a blast hit.
#-----------------------------------------------------------------

package Magena::BlastHit;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use parent qw( Commons::Base );
use Magena::BlastHSP;

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 id          => { post => \&_process_id },
	 description => { post => \&_process_desc },
	 db          => { readonly => 1 },
	 os          => { readonly => 1 },
	 gn          => { readonly => 1 },
	 length      => { type => Commons::Base->INTEGER },
	 evalue      => { type => Commons::Base->FLOAT },
	 score       => { type => Commons::Base->INTEGER },
	 strand      => { type => Commons::Base->INTEGER },
	 hsps        => { type => 'Magena::BlastHSP',
			  is_array => 1 },
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

#-----------------------------------------------------------------
# Post-processing of the just set ID. Split the current ID into DB and
# ID. Examples of possible IDs are:
#    gi|261264910|gb|GQ983543.1|
#    UNIPROT:VSPH_TRIJE
#    tr_Q1D632_Q1D632_MYXXD
# How to split is controlled by configuration properties.
# -----------------------------------------------------------------
sub _process_id {
    my $self = shift;
    my $regex = CFG->get ('blast.id.separator.regex');
    my $separator = CFG->get ('blast.id.separator');
    if ($regex) {
	($separator) = $self->{id} =~ /$regex/
	    unless $separator;
    }
    $separator = '|'
	unless $separator;
    my ($db, $id) = split (/\Q$separator\E/, $self->{id});
    if ($db and $id) {
	$self->{db} = $db;
	$self->{id} = $id;
    }
}

#-----------------------------------------------------------------
# Post-processing of the just set description. Split the current
# description into several fields: Species, Gene Name and
# Description. Examples of possible description forms are:
#
#  B0ZT25 Snake venom serine protease homolog OS=Trimeresurus jerdonii PE=1 SV=1
#  DES=GAF domain/GGDEF domain protein#OS=Myxococcus xanthus (strain DK 1622) GN=MXAN_3705
#  growth differentiation factor 9 [Ailuropoda melanoleuca]\cAgb|ACX55815.1|
#
# How to split is controlled by configuration properties.
# -----------------------------------------------------------------
sub _process_desc {
    my $self = shift;

    my $matching = Commons::Config->matching_by_key ('blast.desc.type.prefix');
    return unless keys %$matching > 0;
    my ($desc0) = $self->{description};   # original description line
    my $regex;

#    $self->{desc0} = $self->{description};  # only for debugging

    $self->_extract_from_desc ($desc0, $matching->{'desc.regex'}, 'description');
    $self->_extract_from_desc ($desc0, $matching->{'os.regex'}, 'os');
    $self->_extract_from_desc ($desc0, $matching->{'gn.regex'}, 'gn');
}

# extract from $desc0 using $regex and store result under $target_key
sub _extract_from_desc {
    my ($self, $desc0, $regex, $target_key) = @_;
    if ($regex) {
	$regex =~ s|^/||o; $regex =~ s|/$||o;  # conf may have slashes, remove them
	my ($part) = $desc0 =~ /$regex/;
	if ($part) {
	    $part =~ s/^\s*//o; $part =~ s/\s*$//o;  # trip whitespaces
	    $self->{$target_key} = $part;
	}
    }
}

1;
__END__

UNIPROT:VSPH_TRIJE B0ZT25 Snake venom serine protease homolog OS=Trimeresurus jerdonii PE=1 SV=1

DES=GGDEF/HAMP domain protein#OS=Neptuniibacter caesariensis GN=MED92_15453

Short name: OS (or Method name: os)
Full name: Original species
Value: Neptuniibacter caesariensis
Regex to get value: /#OS=(.*)(\s+\w\w=)?

Examples of description:

(EBI) Venom serine proteinase 2A OS=Trimeresurus gramineus GN=TLG2A PE=2 SV=1

(Atlantis) DES=GAF domain/GGDEF domain protein#OS=Myxococcus xanthus (strain DK 1622) GN=MXAN_3705

(NCBI) growth differentiation factor 9 [Canis lupus familiaris]\cAgb|ACX55815.1| growth differentiation factor 9 [Canis lupus familiaris]

growth/differentiation factor 9 precursor [Bos taurus]\cAsp|Q9GK68.1|GDF9_BOVIN RecName: Full=Growth/differentiation factor 9; Short=GDF-9; Flags: Precursor\cAgb|AAG38106.1|AF307092_1 growth differentiation factor 9 precursor [Bos taurus]\cAdbj|BAB39768.1| growth differentiation factor-9 [Bos taurus]\cAgb|ACX50985.1| growth differentiation factor 9 [Bos taurus]\cAgb|DAA27470.1| growth/differentiation factor 9 precursor [Bos taurus]


Examples of IDs:

gi|261264910|gb|GQ983543.1|
UNIPROT:VSPH_TRIJE
tr_Q1D632_Q1D632_MYXXD

The best way to tackle this is to use a separate configurable regexp
for each item.

a. Description
b. Species
c. Gene name

On 31 May 2010 14:00, Martin Senger <martin.senger@kaust.edu.sa> wrote:
> I am struggling with the format of the description line of the hits in the
> blast results. I am not sure how to parse it in order to be able to parse
> different blast results. I understand that various blasts have different
> data in this line - so our tool has to be configurable. But I can make
> configurable only what I understand :-)
>
> For example, here are three different description lines, using, in this
> order, blast from you, from EBi and from NCBI:
>
> 1) DES=GAF domain/GGDEF domain protein#OS=Myxococcus xanthus (strain DK
> 1622) GN=MXAN_3705

a. GAF domain/GGDEF domain protein
b. Myxococcus xanthus (strain DK 1622)
c. MXAN_3705

> 2) Venom serine proteinase 2A OS=Trimeresurus gramineus GN=TLG2A PE=2 SV=1

a. Venom serine proteinase 2A
b. Trimeresurus gramineus
c. TLG2A

> 3) growth differentiation factor 9 [Canis lupus familiaris]\cAgb|ACX55815.1|
> growth differentiation factor 9 [Canis lupus familiaris]

a. growth differentiation factor 9
b. Canis lupus familiaris
c. Agb

#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 43;

use FindBin qw( $Bin );
use File::Temp;
use Commons::Test;

use Magena::BlastHit;
ok(1);
diag( "Testing Magena::BlastHit" );

use Commons::Config;
$CFG->add (Commons::Test::test_file ('test_config.cfg'));

#
# _process_id()
#
do_id ('gi|261264910|gb|GQ983543.1|', 'gi', '261264910');
do_id ('gi|261:2649_10|gb|GQ983543.1|', 'gi', '261:2649_10');

do_id ('UNIPROT:VSPH_TRIJE', 'UNIPROT', 'VSPH_TRIJE');
do_id ('UNIPROT:VSPH|TRI_JE', 'UNIPROT', 'VSPH|TRI_JE');

do_id ('tr_Q1D632_Q1D632_MYXXD', 'tr', 'Q1D632');
do_id ('tr_Q1:D6|32_Q1D632_MYXXD', 'tr', 'Q1:D6|32');

sub do_id {
    my ($id, $expected_db, $expected_id) = @_;
    my $hit = Magena::BlastHit->new (id => $id);
    is ($hit->id, $expected_id, '_process_id: id');
    is ($hit->db, $expected_db, '_process_id: db');
}

#
# _process_desc()
#

#        $dtype,  $desc0, $desc,  $gn,   $os
do_desc ("dummy", $desc0, $desc0, undef, undef);
do_desc (
    'blast.ebi',
    'B0ZT25 Snake venom serine protease homolog OS=Trimeresurus jerdonii GN=junk PE=1 SV=1',
    'Snake venom serine protease homolog',
    'Trimeresurus jerdonii',
    'junk');
do_desc (
    'blast.ebi',
    'B0ZT25 Snake venom serine protease homolog OS=Trimeresurus jerdonii',
    'Snake venom serine protease homolog',
    'Trimeresurus jerdonii',
    undef);
do_desc (
    'blast.ebi',
    'B0ZT25 Snake venom serine protease homolog',
    'Snake venom serine protease homolog',
    undef,
    undef);
do_desc (
    'blast.kaust',
    'DES=GAF domain/GGDEF domain protein#OS=Myxococcus xanthus (strain DK 1622) GN=MXAN_3705',
    'GAF domain/GGDEF domain protein',
    'Myxococcus xanthus (strain DK 1622)',
    'MXAN_3705');
do_desc (
    'blast.kaust',
    'DES=GAF domain/GGDEF domain protein#OS=Myxococcus xanthus (strain DK 1622)',
    'GAF domain/GGDEF domain protein',
    'Myxococcus xanthus (strain DK 1622)',
    undef);
do_desc (
    'blast.kaust',
    'DES=GAF domain/GGDEF domain protein',
    'GAF domain/GGDEF domain protein',
    undef,
    undef);
do_desc (
    'blast.ncbi',
    'growth differentiation factor 9 [Ailuropoda melanoleuca]\cAgb|ACX55815.1|',
    'growth differentiation factor 9',
    'Ailuropoda melanoleuca',
    'Agb');
do_desc (
    'blast.ncbi',
    'growth differentiation factor 9 [Ailuropoda melanoleuca]',
    'growth differentiation factor 9',
    'Ailuropoda melanoleuca',
    undef);
do_desc (
    'blast.ncbi',
    'growth differentiation factor 9',
    'growth differentiation factor 9',
    undef,
    undef);

sub do_desc {
    my ($dtype, $desc0, $desc, $os, $gn) = @_;
    $CFG->set ('blast.desc.type.prefix', $dtype);
    my $hit = Magena::BlastHit->new (description => $desc0);
    is ($hit->description, $desc, "_process_desc: desc ($dtype)");
    is ($hit->os,          $os,   "_process_desc: os ($dtype)");
    is ($hit->gn,          $gn,   "_process_desc: gn ($dtype)");
}

1;
__END__

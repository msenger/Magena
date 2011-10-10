#!perl -T

use Test::More tests => 10;

BEGIN {
    use lib './';
    use_ok ('Commons::Test');
    use_ok ('Commons::Utilities');
    use_ok ('Commons::Config');
    use_ok ('Commons::Base');
    use_ok ('Magena::Utilities');
    use_ok ('Magena::BlastResults');
    use_ok ('Magena::BlastResult');
    use_ok ('Magena::BlastHit');
    use_ok ('Magena::Annotation');
    use_ok ('Magena::Server');
}

diag( "Testing Magena $Magena::Version::VERSION, Perl $], $^X" );

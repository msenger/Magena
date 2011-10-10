#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 7;

use Commons::Test;

use Magena::BlastResult;
ok(1);
diag( "Testing Magena::BlastResult" );

Commons::Test::do_attrs_check
    ("Magena::BlastResult",
     { id => 'a',
       description => 'b',
       length => 25,
     });

1;
__END__

#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 19;

use Commons::Test;

use Magena::Annotation;
ok(1);
diag( "Testing Magena::Annotation" );

Commons::Test::do_attrs_check
    ("Magena::Annotation",
     { id     => 'a',
       source => 'b',
       type   => 'c',
       start  => 25,
       end    => 50,
       score  => 3e-25,
       strand => '+',
       phase  => 2,
     });

# attributes
my $annot = Magena::Annotation->new();
my $attrs = { ATTR_TAXON()    => 'k',
	      ATTR_CREATED()  => 'l',
	      ATTR_MODIFIED() => 'm',
	      ATTR_CURATOR()  => 'n',
};

# ...add them one by one
foreach my $key (keys %$attrs) {
    $annot->attributes ({ $key => $attrs->{$key} });
}
# ...then read them all
my $read_attrs = $annot->attributes;
ok (Commons::Test::compare_arrays
    ([sort keys (%$attrs)],   [sort keys (%$read_attrs)]),   'attrs: keys');
ok (Commons::Test::compare_arrays
    ([sort values (%$attrs)], [sort values (%$read_attrs)]), 'attrs: values');

1;
__END__

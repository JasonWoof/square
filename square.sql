use square;
drop table if exists square;
create table square (
	id int unique auto_increment,
	parent int,
	position int, /* in parent */
	tog0 bool,
	tog1 bool,
	tog2 bool,
	tog3 bool,
	id0 int,
	id1 int,
	id2 int,
	id3 int
);

drop table if exists square_mama;
create table square_mama (
	id int unique,
	mama int
);

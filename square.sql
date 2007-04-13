use square;
drop table if exists square;
create table square (
	id int unique auto_increment,
	address blob,
	pixels blob
);

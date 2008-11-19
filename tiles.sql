drop table if exists tiles;
create table tiles (
	id int unique auto_increment,
	url varchar(255) not null default "",
	t32 blob,
	t64 blob,
	t128 blob
);

create database testing;
use testing;

create table testing.User
(
	idUser int auto_increment,
	username varchar(16) not null,
	password varchar(64) not null,
	constraint User_pk
		primary key (idUser)
);

insert into testing.User (username, `password`) values ('user', password('go'));

GRANT ALL PRIVILEGES ON testing.* TO 'myapp'@'localhost' IDENTIFIED BY 'myapp-secret';
FLUSH PRIVILEGES;
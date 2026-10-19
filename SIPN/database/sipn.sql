-- SIPN - Sistem Informasi Pengelolaan Nilai
-- Import lewat HeidiSQL/phpMyAdmin (Laragon). Database dibuat otomatis.

CREATE DATABASE IF NOT EXISTS db_smk CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_smk;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS nilai_rapor, mengajar, mapel, guru, siswa, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('admin','guru','siswa') NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id), UNIQUE KEY uq_username (username)
) ENGINE=InnoDB;
CREATE TABLE siswa (
  id int(11) NOT NULL AUTO_INCREMENT,
  nis varchar(10) NOT NULL,
  nama varchar(100) NOT NULL,
  kelas varchar(10) NOT NULL,
  jenis_kelamin enum('L','P') NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uq_nis (nis), UNIQUE KEY uq_siswa_user_id (user_id),
  CONSTRAINT fk_siswa_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE guru (
  id int(11) NOT NULL AUTO_INCREMENT,
  nip varchar(20) NOT NULL,
  nama varchar(100) NOT NULL,
  jenis_kelamin enum('L','P') NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uq_nip (nip), UNIQUE KEY uq_guru_user_id (user_id),
  CONSTRAINT fk_guru_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE mapel (
  id int(11) NOT NULL AUTO_INCREMENT,
  kode_mapel varchar(10) NOT NULL,
  nama_mapel varchar(100) NOT NULL,
  guru_id int(11) DEFAULT NULL,
  PRIMARY KEY (id), UNIQUE KEY uq_kode_mapel (kode_mapel), KEY guru_id (guru_id),
  CONSTRAINT fk_mapel_guru FOREIGN KEY (guru_id) REFERENCES guru (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE mengajar (
  id int(11) NOT NULL AUTO_INCREMENT,
  mapel_id int(11) NOT NULL,
  siswa_id int(11) NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uq_mengajar (mapel_id, siswa_id), KEY siswa_id (siswa_id),
  CONSTRAINT fk_mengajar_mapel FOREIGN KEY (mapel_id) REFERENCES mapel (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mengajar_siswa FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE nilai_rapor (
  id int(11) NOT NULL AUTO_INCREMENT,
  siswa_id int(11) NOT NULL,
  mapel_id int(11) NOT NULL,
  nilai_kkm int(11) NOT NULL,
  nilai_pengetahuan int(11) NOT NULL,
  nilai_keterampilan int(11) NOT NULL,
  Keterangan varchar(50) NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uq_nilai_mapel_siswa (mapel_id, siswa_id), KEY siswa_id (siswa_id), KEY mapel_id (mapel_id),
  CONSTRAINT fk_nilai_siswa FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_mapel FOREIGN KEY (mapel_id) REFERENCES mapel (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id,username,password,role) VALUES
(1,'admin','$2y$12$FhGemJ.nZaoJiE64bWUUeOqIXY.76hN1FTW.93DV0VnkSTJ8lwE/e','admin'),
(2,'guru1','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(3,'guru2','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(4,'guru3','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(5,'guru4','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(6,'guru5','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(7,'guru6','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(8,'guru7','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(9,'guru8','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(10,'guru9','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(11,'guru10','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(12,'guru11','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(13,'guru12','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(14,'guru13','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(15,'guru14','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(16,'guru15','$2y$12$P.3MWKT7v47jk1hKar4QseT3ujwiyiEGEU4/DggDgWJCZE0NdOId2','guru'),
(17,'siswa1','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(18,'siswa2','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(19,'siswa3','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(20,'siswa4','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(21,'siswa5','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(22,'siswa6','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(23,'siswa7','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(24,'siswa8','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(25,'siswa9','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(26,'siswa10','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(27,'siswa11','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(28,'siswa12','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(29,'siswa13','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(30,'siswa14','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa'),
(31,'siswa15','$2y$12$J3xigHvm0t9nj5/UgPpamuJTZwUM8lcIpXYxyhDJd9pPcMoE4hEjq','siswa');

INSERT INTO siswa (id,nis,nama,kelas,jenis_kelamin,user_id) VALUES
(1,'2026101','Andi Pratama','XI RPL 1','L',17),
(2,'2026102','Bunga Citra','XI RPL 1','P',18),
(3,'2026103','Cahya Nugraha','XI RPL 1','L',19),
(4,'2026104','Dina Oktaviani','XI RPL 1','P',20),
(5,'2026105','Eko Prasetyo','XI RPL 1','L',21),
(6,'2026106','Fitri Handayani','XI RPL 1','P',22),
(7,'2026107','Gilang Ramadhan','XI RPL 1','L',23),
(8,'2026108','Hana Safitri','XI RPL 1','P',24),
(9,'2026109','Indra Kusuma','XI RPL 2','L',25),
(10,'2026110','Jihan Aulia','XI RPL 2','P',26),
(11,'2026111','Kevin Saputra','XI RPL 2','L',27),
(12,'2026112','Lia Permata','XI RPL 2','P',28),
(13,'2026113','Malik Ibrahim','XI RPL 2','L',29),
(14,'2026114','Nabila Putri','XI RPL 2','P',30),
(15,'2026115','Omar Faisal','XI RPL 2','L',31);

INSERT INTO guru (id,nip,nama,jenis_kelamin,user_id) VALUES
(1,'198001012005011001','Budi Santoso','L',2),
(2,'198001012005011002','Siti Aminah','P',3),
(3,'198001012005011003','Ahmad Fauzi','L',4),
(4,'198001012005011004','Dewi Lestari','P',5),
(5,'198001012005011005','Rudi Hartono','L',6),
(6,'198001012005011006','Rina Marlina','P',7),
(7,'198001012005011007','Agus Salim','L',8),
(8,'198001012005011008','Nur Hidayah','P',9),
(9,'198001012005011009','Hendra Wijaya','L',10),
(10,'198001012005011010','Yuni Astuti','P',11),
(11,'198001012005011011','Dedi Kurniawan','L',12),
(12,'198001012005011012','Lilis Suryani','P',13),
(13,'198001012005011013','Taufik Hidayat','L',14),
(14,'198001012005011014','Maya Anggraini','P',15),
(15,'198001012005011015','Wawan Setiawan','L',16);

INSERT INTO mapel (id,kode_mapel,nama_mapel,guru_id) VALUES
(1,'PWEB','Pemrograman Web',1),
(2,'BASDAT','Basis Data',2),
(3,'PBO','Pemrograman Berorientasi Objek',3),
(4,'PPKN','Pendidikan Pancasila',4),
(5,'MTK','Matematika',5),
(6,'BIND','Bahasa Indonesia',6),
(7,'BING','Bahasa Inggris',7),
(8,'PJOK','Pendidikan Jasmani',8),
(9,'PKK','Produk Kreatif dan Kewirausahaan',9),
(10,'DDG','Dasar Desain Grafis',10);

INSERT INTO mengajar (id,mapel_id,siswa_id) VALUES
(1,1,1),
(2,2,1),
(3,3,1),
(4,4,1),
(5,2,2),
(6,3,2),
(7,4,2),
(8,5,2),
(9,3,3),
(10,4,3),
(11,5,3),
(12,6,3),
(13,4,4),
(14,5,4),
(15,6,4),
(16,7,4),
(17,5,5),
(18,6,5),
(19,7,5),
(20,8,5),
(21,6,6),
(22,7,6),
(23,8,6),
(24,9,6),
(25,7,7),
(26,8,7),
(27,9,7),
(28,10,7),
(29,8,8),
(30,9,8),
(31,10,8),
(33,9,9),
(34,10,9),
(37,10,10),
(49,1,13),
(53,1,14),
(54,2,14),
(57,1,15),
(58,2,15),
(59,3,15);

INSERT INTO nilai_rapor (id,siswa_id,mapel_id,nilai_kkm,nilai_pengetahuan,nilai_keterampilan,Keterangan) VALUES
(1,1,1,75,90,94,'Tuntas'),
(2,1,2,75,90,97,'Tuntas'),
(3,1,3,75,74,76,'Tuntas'),
(4,1,4,75,94,95,'Tuntas'),
(5,2,2,75,73,71,'Belum Tuntas'),
(6,2,3,75,90,84,'Tuntas'),
(7,2,4,75,71,70,'Belum Tuntas'),
(8,2,5,75,96,67,'Tuntas'),
(9,3,3,75,87,93,'Tuntas'),
(10,3,4,75,72,65,'Belum Tuntas'),
(11,3,5,75,95,69,'Tuntas'),
(12,3,6,75,65,67,'Belum Tuntas'),
(13,4,4,75,74,80,'Tuntas'),
(14,4,5,75,63,94,'Tuntas'),
(15,4,6,75,82,93,'Tuntas'),
(16,4,7,75,74,98,'Tuntas'),
(17,5,5,75,76,83,'Tuntas'),
(18,5,6,75,93,65,'Tuntas'),
(19,5,7,75,67,94,'Tuntas'),
(20,5,8,75,79,91,'Tuntas'),
(21,6,6,75,97,70,'Tuntas'),
(22,6,7,75,78,85,'Tuntas'),
(23,6,8,75,76,97,'Tuntas'),
(24,6,9,75,80,66,'Belum Tuntas'),
(25,7,7,75,66,71,'Belum Tuntas'),
(26,7,8,75,87,71,'Tuntas'),
(27,7,9,75,80,89,'Tuntas'),
(28,7,10,75,66,66,'Belum Tuntas'),
(29,8,8,75,62,78,'Belum Tuntas'),
(30,8,9,75,75,68,'Belum Tuntas'),
(31,8,10,75,92,89,'Tuntas'),
(33,9,9,75,66,77,'Belum Tuntas'),
(34,9,10,75,79,86,'Tuntas'),
(37,10,10,75,88,72,'Tuntas'),
(49,13,1,75,81,66,'Belum Tuntas'),
(53,14,1,75,75,93,'Tuntas'),
(54,14,2,75,78,65,'Belum Tuntas'),
(57,15,1,75,66,70,'Belum Tuntas'),
(58,15,2,75,75,80,'Tuntas'),
(59,15,3,75,62,88,'Tuntas');

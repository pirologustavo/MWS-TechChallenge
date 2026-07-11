# rds.tf

resource "aws_db_subnet_group" "rds_subnets" {
  name       = "mws-rds-subnet-group-v2"
  subnet_ids = [aws_subnet.public_1.id, aws_subnet.public_2.id]
}

resource "aws_security_group" "rds_sg" {
  name   = "mws-rds-sg"
  vpc_id = aws_vpc.mws_vpc.id

  ingress {
    from_port   = 3306
    to_port     = 3306
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "mws-rds-sg"
  }
}

resource "aws_db_instance" "mysql" {
  identifier             = "mws-db-rds-v2"
  allocated_storage      = 20
  engine                 = "mysql"
  engine_version         = "8.0"
  instance_class         = "db.t3.micro"
  db_name                = "mws_db"
  username               = "root"
  password               = "rootdatabase2026"
  db_subnet_group_name   = aws_db_subnet_group.rds_subnets.name
  vpc_security_group_ids = [aws_security_group.rds_sg.id]
  skip_final_snapshot    = true
  publicly_accessible    = true
}
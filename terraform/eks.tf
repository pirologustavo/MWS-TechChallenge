# eks.tf

data "aws_iam_role" "labrole" {
  name = "LabRole"
}

resource "aws_eks_cluster" "mws_cluster" {
  name     = "mws-cluster"
  role_arn = data.aws_iam_role.labrole.arn

  vpc_config {
    subnet_ids         = [aws_subnet.public_1.id, aws_subnet.public_2.id]
    security_group_ids = [aws_security_group.rds_sg.id]
  }
}

resource "aws_eks_node_group" "mws_nodes" {
  cluster_name    = aws_eks_cluster.mws_cluster.name
  node_group_name = "mws-node-group"
  node_role_arn   = data.aws_iam_role.labrole.arn
  subnet_ids      = [aws_subnet.public_1.id, aws_subnet.public_2.id]

  instance_types = ["t3.medium"]

  scaling_config {
    desired_size = 2
    max_size     = 3
    min_size     = 1
  }

  update_config {
    max_unavailable = 1
  }
}
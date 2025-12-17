# network.tf

data "aws_vpc" "main" {
  filter {
    name = "tag:Name"
    values = [var.vpc_name]
  }
}

data "aws_subnets" "app" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.app_subnet_names
  }
}

data "aws_subnets" "data" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.data_subnet_names
  }
}

data "aws_subnets" "web" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.web_subnet_names
  }
}

data "aws_subnet" "app" {
  for_each = toset(data.aws_subnets.app.ids)
  id       = each.value
}

data "aws_subnet" "data" {
  for_each = toset(data.aws_subnets.data.ids)
  id       = each.value
}

data "aws_subnet" "web" {
  for_each = toset(data.aws_subnets.web.ids)
  id       = each.value
}



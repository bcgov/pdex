# network.tf

data "aws_vpc" "main" {
  id = var.vpc_id
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

data "aws_subnets" "pod" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.pod_subnet_names
  }
}

# data and locals here are need to prevent wrong assignment of subnets to ENIConfigs. The ENIConfig must reference the correct subnet for each AZ, and the only way to guarantee this is to pull the full subnet objects and map them by AZ.
# This was happening:
# ENIConfig ca-central-1a points to a 1b subnet
# ENIConfig ca-central-1b points to a 1a subnet
# Pull full subnet objects so we can read AZ + Name
data "aws_subnet" "pod" {
  for_each = toset(data.aws_subnets.pod.ids)
  id       = each.value
}
# Map AZ -> subnet_id (guaranteed correct)
locals {
  pod_subnet_by_az = {
    for id, s in data.aws_subnet.pod :
    s.availability_zone => s.id
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

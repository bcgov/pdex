# Redis

resource "aws_elasticache_replication_group" "workbc_redis_rg" {
	automatic_failover_enabled	=	true
	preferred_cache_cluster_azs	=	["ca-central-1a", "ca-central-1b"]
	replication_group_id		=	"workbc-rep-group"
	description			=	"Redis replication group for Drupal"
	node_type			=	"cache.t4g.small"
	num_cache_clusters		=	2
	engine_version			=	"6.x"
	parameter_group_name		=	"default.redis6.x"
	port				=	6379
	
	lifecycle {
		ignore_changes	=	[num_cache_clusters]
	}
	
	subnet_group_name		=	aws_elasticache_subnet_group.default.name
	security_group_ids		=	[aws_security_group.allow_redis.id]
}

resource "aws_elasticache_cluster" "replica" {
	count 		= 	1
	cluster_id	=	"workbc-rep-group-${count.index}"
	replication_group_id	=	aws_elasticache_replication_group.workbc_redis_rg.id
}

resource "aws_elasticache_subnet_group" "default" {
	name		=	"redis-subnet-group-drupal"
	subnet_ids	=	data.aws_subnets.app.ids
}



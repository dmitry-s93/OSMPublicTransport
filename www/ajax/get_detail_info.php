<?php
include_once ('../include/config.php');

$sql_query=pg_query("
	SELECT
		transport_routes.id,
		transport_routes.tags->'route' as type,
		transport_routes.tags->'ref' as ref,
		transport_routes.tags->'from' as from,
		transport_routes.tags->'to' as to
	FROM
		transport_routes,
		relation_members
	WHERE
		relation_members.member_id=".$_POST['id']." and
		relation_members.member_role in ('stop','stop_exit_only','stop_entry_only','platform','platform_entry_only','platform_exit_only') and
		transport_routes.id=relation_members.relation_id
	ORDER BY
		type,
		substring(transport_routes.tags->'ref' from '^\\d+')::int;
");

if (pg_num_rows($sql_query) > 0) {
	while ($row = pg_fetch_assoc($sql_query)){
		$rows[] = $row;
	}
	echo json_encode($rows);
}
?>

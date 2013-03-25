<?php

function liked_content_init() {
  
  elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'liked_content_owner_block');
  elgg_register_plugin_hook_handler('register', 'menu:entity', 'liked_content_entity_menu', 1000);
  elgg_register_page_handler('liked_content', 'liked_content_page_handler');
  
  if (elgg_is_active_plugin('au_widgets_framework')) {
	elgg_register_widget_type(
		  'liked_content',
		  elgg_echo('liked_content:widget:your_likes:title'),
		  elgg_echo('liked_content:widget:your_likes:description'),
		  'profile,dashboard,groups,index',
		  TRUE
	);
  }
  
  add_group_tool_option('liked_content', elgg_echo('liked_content:group:enable'), true);
}


function liked_content_page_handler($page) {
  $dbprefix = elgg_get_config('dbprefix');
  $likes_metastring = get_metastring_id('likes');
  
  switch ($page[0]) {
	case 'group':
	  $entity = get_entity($page[1]);
	  if (!elgg_instanceof($entity, 'group') || $entity->liked_content_enable == 'no') {
		return false;
	  }
	  elgg_set_page_owner_guid($entity->guid);
	  
	  elgg_push_breadcrumb($entity->name, $entity->getURL());
	  elgg_push_breadcrumb(elgg_echo('liked_content:liked_content'));
	  
	  $options = array(
		'container_guid' => $entity->guid,
		'annotation_names' => array('likes'),
		'selects' => array("(SELECT count(distinct l.id) FROM {$dbprefix}annotations l WHERE l.name_id = $likes_metastring AND l.entity_guid = e.guid) AS likes"),
		'order_by' => 'likes DESC',
		'full_view' => false
	  );
	  
	  $content = elgg_list_entities_from_annotations($options);
	  if (!$content) {
		$content = elgg_echo('liked_content:noresults');
	  }
	  
	  $title = elgg_echo('liked_content:group:most_liked');
	  
	  $layout = elgg_view_layout('content', array(
		  'title' => elgg_view_title($title),
		  'content' => $content,
		  'filter' => false,
	  ));
	  
	  echo elgg_view_page($title, $layout);
	  return true;
	  
	  break;
	case 'user':
	  $entity = get_user_by_username($page[1]);
	  if (!elgg_instanceof($entity, 'user')) {
		return false;
	  }
	  elgg_set_page_owner_guid($entity->guid);
	  
	  elgg_push_breadcrumb($entity->name, $entity->getURL());
	  elgg_push_breadcrumb(elgg_echo('liked_content:liked_content'));
	  
	  $filter = get_input('filter');
	  
	  $options = array(
		  'annotation_names' => array('likes'),
		  'annotation_owner_guids' => array($entity->guid),
		  'order_by' => 'maxtime DESC',
		  'full_view' => false,
	  );
		
	  if ($filter == 'most_liked') {
		$options = array(
		  'container_guid' => $entity->guid,
		  'annotation_names' => array('likes'),
		  'selects' => array("(SELECT count(distinct l.id) FROM {$dbprefix}annotations l WHERE l.name_id = $likes_metastring AND l.entity_guid = e.guid) AS likes"),
		  'order_by' => 'likes DESC',
		  'full_view' => false
		);
	  }
	  
	  $content = elgg_list_entities_from_annotations($options);
	  if (!$content) {
		$content = elgg_echo('liked_content:noresults');
	  }
	  
	  $title = elgg_echo('liked_content:liked_content');
	  
	  $layout = elgg_view_layout('content', array(
		  'title' => elgg_view_title($title),
		  'content' => $content,
		  'filter' => elgg_view('liked_content/navigation/filter'),
	  ));
	  
	  echo elgg_view_page($title, $layout);
	  return true;
	  
	  break;
  }
  
  return false;
}


function liked_content_owner_block($hook, $type, $return, $params) {
  if (elgg_instanceof($params['entity'], 'group') && $params['entity']->liked_content_enable != 'no') {
	$url = 'liked_content/group/' . $params['entity']->guid;
	$item = new ElggMenuItem('liked_content', elgg_echo('liked_content:group:liked_content'), $url);
	$return[] = $item;
  }
  
  if (elgg_instanceof($params['entity'], 'user')) {
	$url = 'liked_content/user/' . $params['entity']->username;
	$item = new ElggMenuItem('liked_content', elgg_echo('liked_content:user:liked_content'), $url);
	$return[] = $item;
  }
  
  return $return;
}


function liked_content_set_defaults($widget) {
  if ($widget->defaults_set == 1) {
	return;
  }
  
  $widget->eligo_sortby = 'mostliked';
  $widget->eligo_sortby_dir = 'desc';
  
  // set defaults depending on what kind of widget it is
  $container = $widget->getContainerEntity();
  
  if (!elgg_instanceof($container, 'group')) {
	// profile/dashboard/index
	$widget->eligo_owners = 'all';
  }
  else {
	// groups
	$widget->eligo_owners = 'thisgroup';
  }
  
  if (elgg_instanceof($container, 'user')) {
	$widget->my_likes = 0;
  }
  
  $widget->defaults_set = 1;
}


function liked_content_entity_menu($hook, $type, $return, $params) {
  if (elgg_get_context() != 'liked_content_widget') {
	return $return;
  }
  
  foreach ($return as $key => $item) {
	if ($item->getName() != 'likes_count') {
	  unset($return[$key]);
	}
  }
  
  return $return;
}

elgg_register_event_handler('init', 'system', 'liked_content_init');

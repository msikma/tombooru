create table /*_*/tombooru_post (
  id int unsigned auto_increment primary key,
  page_id int unsigned not null,                -- fk to mw page.page_id (this must be an uploaded file)
  filename varchar(300) not null,               -- canonical name of the post - equivalent to page.page_title (has underscores)
  created_at timestamp not null default current_timestamp,

  unique key (page_id),
  index (filename)
);

create table /*_*/tombooru_post_data (
  id int unsigned auto_increment primary key,   -- fk to tombooru_post.id
  description_page_id int unsigned null,        -- page that stores the description
  notes_page_id int unsigned null,              -- page that stores the notes
  rating enum('safe', 'questionable', 'explicit') null,
  favorites int unsigned not null default 0,
  score int signed not null default 0,          -- upvotes minus downvotes
  upvotes int unsigned not null default 0,
  downvotes int unsigned not null default 0,
  media_type varchar(300) null,                 -- typically "image" or "video"; others may be implemented in the future
  license varchar(300) null,                    -- free input, to be constrained by the app code
  preview_filename varchar(300) default null,   -- image file that serves as preview for video files
  original_publication_date datetime null,      -- when the media was originally published (not on Tombooru, but at the source)
  updated_at timestamp not null default current_timestamp,
  status enum('active', 'flagged', 'pending_approval', 'deleted') null,
  is_ai_generated bool not null default 0,
  poster_user_id int unsigned null,             -- references user.user_id
  poster_ua varchar(300) null,                  -- e.g. whether this was posted via a bot or app, normally empty
  approver_user_id int unsigned null,           -- references user.user_id

  index (rating),
  index (status),
  index (media_type),
  index (license),
  index (preview_filename),
  index (original_publication_date),
  index (poster_user_id),
  index (approver_user_id),
  index (favorites),
  index (score),
  index (is_ai_generated),
  index (description_page_id),
  index (notes_page_id),
  constraint fk_post_data_post foreign key (id) references /*_*/tombooru_post(id) on delete cascade
);

create table /*_*/tombooru_tag (
  id int unsigned auto_increment primary key,
  name varchar(300) not null unique,            -- case insensitive, case preserving
  category varchar(300) not null default '',    -- free input; the app recognizes certain special terms here
  description_page_id int unsigned null,        -- page that stores the description
  notes_page_id int unsigned null,              -- page that stores the notes
  count int unsigned default 0,
  aliased_to int unsigned default null,
  created_at timestamp not null default current_timestamp,

  index (category),
  index (description_page_id),
  index (notes_page_id),
  index (count),
  index (aliased_to),
  index (created_at),
  constraint fk_aliased_to_id foreign key (aliased_to) references /*_*/tombooru_tag(id) on delete set null
);

create table /*_*/tombooru_tag_category (
  id int unsigned auto_increment primary key,
  name varchar(300) not null unique,
  slug varchar(300) not null unique,            -- case insensitive, case preserving
  icon varchar(300) null,
  color varchar(300) null,
  header int not null default 1,                -- whether the tag category header is displayed
  description_page_id int unsigned null,        -- page that stores the description
  notes_page_id int unsigned null,              -- page that stores the notes
  properties varchar(300) not null default '',  -- special properties recognized by the app
  count int unsigned default 0,
  ordering int default 0,
  created_at timestamp not null default current_timestamp,

  index (description_page_id),
  index (notes_page_id),
  index (count),
  index (created_at)
);

create table /*_*/tombooru_post_set (
  id int unsigned auto_increment primary key,
  name varchar(300) not null,                   -- name of the set/series
  description_page_id int unsigned null,        -- page that stores the description
  notes_page_id int unsigned null,              -- page that stores the notes
  is_primary bool not null default 0,
  creator_user_id int unsigned null,            -- references user.user_id
  created_at timestamp not null default current_timestamp,

  index (name),
  index (description_page_id),
  index (notes_page_id),
  index (creator_user_id),
  constraint fk_post_set_creator_user foreign key (creator_user_id) references /*_*/user(user_id) on delete set null
);


create table /*_*/tombooru_post_set_post (
  post_set_id int unsigned not null,            -- fk to tombooru_post_set.id
  post_id int unsigned not null,                -- fk to tombooru_post.id

  primary key (post_set_id, post_id),
  constraint fk_post_set_post_set foreign key (post_set_id) references /*_*/tombooru_post_set(id) on delete cascade on update cascade,
  constraint fk_post_set_post_post foreign key (post_id) references /*_*/tombooru_post(id) on delete cascade on update cascade,
  index (post_id)
);
  

create table /*_*/tombooru_post_tag (
  post_id int unsigned not null,
  tag_id int unsigned not null,

  primary key (post_id, tag_id),
  constraint fk_post_tag_post foreign key (post_id) references /*_*/tombooru_post(id) on delete cascade on update cascade,
  constraint fk_post_tag_tag foreign key (tag_id) references /*_*/tombooru_tag(id) on delete cascade on update cascade,
  index (tag_id)
);

create table /*_*/tombooru_post_source (
  id int unsigned auto_increment primary key,   -- unique ID for each source
  post_id int unsigned not null,                -- fk to tombooru_post.id
  url varchar(500) not null,                    -- the URL source of the post
  archive_url varchar(500) not null,            -- archive URL pointing to the same page
  created_at timestamp not null default current_timestamp,

  constraint fk_post_source_post foreign key (post_id) references /*_*/tombooru_post(id) on delete cascade on update cascade,
  index (post_id),
  index (url),
  index (archive_url)
);

create table /*_*/tombooru_post_interaction (
  id int unsigned auto_increment primary key,
  post_id int unsigned not null,                -- fk to tombooru_post.id
  user_id int unsigned not null,                -- fk to user.user_id
  interaction_type enum('favorite', 'upvote', 'downvote') not null,
  created_at timestamp not null default current_timestamp,
  
  unique (post_id, user_id, interaction_type),
  constraint fk_post_interaction_post foreign key (post_id) references /*_*/tombooru_post(id) on delete cascade,
  constraint fk_post_interaction_user foreign key (user_id) references /*_*/user(user_id) -- don't delete, to preserve statistics
);

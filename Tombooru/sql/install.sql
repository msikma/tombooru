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
  license varchar(300) null,                    -- free input, to be constrained by the extension code
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
  type varchar(300) not null default '',        -- free input; the extension recognizes certain special terms here
  description_page_id int unsigned null,        -- page that stores the description
  notes_page_id int unsigned null,              -- page that stores the notes
  count int unsigned default 0,
  created_at timestamp not null default current_timestamp,

  index (type),
  index (description_page_id),
  index (notes_page_id),
  index (count),
  index (created_at)
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
  created_at timestamp not null default current_timestamp,

  constraint fk_post_source_post foreign key (post_id) references /*_*/tombooru_post(id) on delete cascade on update cascade,
  index (post_id),
  index (url)
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

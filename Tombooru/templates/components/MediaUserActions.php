<?php
  $file = $post['file'];
  $original = $file['media']['original'];

  $ranking = $post['ranking'];
  $hasFavorited = @$userPostInteractions['favorite']['state'] ?? false;
  $hasUpvoted = @$userPostInteractions['upvote']['state'] ?? false;
  $hasDownvoted = @$userPostInteractions['downvote']['state'] ?? false;
  $userVote = $hasUpvoted ? 1 : ($hasDownvoted ? -1 : 0);

  $userScaling = @$request['cookies']['tombooru-user-scaling'];
?>
<div class="media-user-interactions">
  <div class="media-info">
    <div class="actions no-margin">
      <div class="action-sets">
        <div class="action-set">
          <a download href="<?= htmlspecialchars($original['url']); ?>" class="item blue active icon" <?= Template::setIcon('download'); ?>>Download</a>
          <a href="#" class="item <?= $hasFavorited ? 'pink active is-faved' : 'blue'; ?> icon favorite" data-tombooru-component="UserFavorite" <?= Template::setIcon('star'); ?> data-favorited="<?= $hasFavorited ? 1 : 0; ?>" data-post-id="<?= $post['pageID']; ?>">Favorite<script>Tombooru.decorateComponent()</script></a>
          <div class="upvote-downvote" data-tombooru-component="UserUpvoteDownvote" data-vote="<?= $userVote; ?>" data-score="<?= $ranking['score']; ?>" data-post-id="<?= $post['pageID']; ?>">
            <div class="set set-upvote">
              <a href="#" class="item green icon icon-only upvote" <?= Template::setIcon('arrow-up'); ?>>Upvote</a>
              <a href="#" class="item green score"><?= $ranking['score']; ?></a>
            </div>
            <div class="set set-downvote">
              <a href="#" class="item red icon icon-only downvote" <?= Template::setIcon('arrow-down'); ?>>Downvote</a>
              <a href="#" class="item red score"><?= $ranking['score']; ?></a>
            </div>
            <script>Tombooru.decorateComponent()</script>
          </div>
        </div>
        <div class="action-set">
          <div class="scaling-selector" data-tombooru-component="UserScalingSelector">
            <select>
              <option value="default" <?= $userScaling === 'default' ? 'selected' : ''; ?>>Default scaling</option>
              <option value="fullwidth" <?= $userScaling === 'fullwidth' ? 'selected' : ''; ?>>Fullwidth</option>
            </select>
            <script>Tombooru.decorateComponent()</script>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

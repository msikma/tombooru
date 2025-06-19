<?php
?>
<div class="tc-big-table no-visited is-article-header" data-default-direction="desc" data-is-sortable="false">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="false" data-is-sortable="false">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-default-active="true" data-is-sortable="false">Name<span class="sorter"></span></th>
          <th data-slug="artist" data-direction="asc" data-active="false" data-is-sortable="false">Artist<span class="sorter"></span></th>
          <th data-slug="category" data-direction="asc" data-active="false" data-is-sortable="false">Tags<span class="sorter"></span></th>
          <th data-slug="created_at" data-direction="asc" data-active="false" data-is-sortable="false">Created<span class="sorter"></span></th>
        </tr>
        <?php if (count($posts) === 0): ?>
          <tr class="notification">
            <td colspan="999">No results.</td>
          </tr>
        <?php endif; ?>
        <?php $previousYear = null; ?>
        <?php foreach ($posts as $post): ?>
          <?php
            // The year; each post that has a different one than the last gets a new section header.
            $year = Template::formatYear($post['data']['originalPublicationDate']);

            // Basic post information.
            $pageID = $post['pageID'];
            $postBlurb = DataHelper::getPostBlurb($post);
            $postPublicationDate = $post['data']['originalPublicationDate'];
            $thumb = @$post['file']['media']['thumb'];
            $dimensions = DataHelper::getImageDimensions($thumb);

            $artistTags = DataHelper::findArtistTags($post['tags']);
            $flatTags = DataHelper::getFlatPostTags($post['tags']);

            $blurb = !empty($postBlurb) ? $postBlurb : $post['file']['name'];
            $date = substr($post['data']['originalPublicationDate'], 0, 10);
          ?>
          <?php if ($year !== $previousYear): ?>
            <tr class="separator"><td colspan="999"></td></tr>
            <tr class="section"><td></td><td colspan="999"><span class="icon-item" <?= Template::setIcon('calendar'); ?>></span><span class="title"><?= $year; ?></span></td></tr>
          <?php endif; ?>
          <tr class="post orientation-<?= htmlspecialchars($dimensions['orientation']); ?>">
            <td class="right"><span class="inner"><?= $post['id']; ?></span></td>
            <td class="blurb highlighted"><span class="inner"><a href="<?= URL::getURL("/posts/view/{$pageID}"); ?>"><?= $blurb; ?></a></span></td>
            <td><span class="inner"><?php
              foreach (($artistTags ?? []) as $artistTag): ?>
                <a href="<?= htmlentities(URL::getTagSearchURL($artistTag)); ?>"><?= htmlentities(str_replace('_', ' ', $artistTag['name'])); ?></a>
              <?php
              endforeach;
            ?></span></td>
            <td class="even-padding tags">
              <span class="inner">
                <div class="actions narrow">
                  <?php foreach ($flatTags as $tag): ?>
                    <?php
                      $name = str_replace('_', ' ', $tag['name']);
                      $color = @$tag['category']['color'] ?? 'green';
                      $isArtistCategory = DataHelper::isSpecialCategory(@$tag['category'], 'artist');
                      if ($isArtistCategory) {
                        continue;
                      }
                    ?>
                    <span class="item <?= $color; ?> tag"><?= htmlspecialchars($name); ?></span>
                  <?php endforeach; ?>
                </div>
              </span>
            </td>
            <td class="right"><span class="inner"><?= $date; ?></span></td>
          </tr>
          <?php $previousYear = $year; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

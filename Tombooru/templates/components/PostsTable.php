<?php
?>
<div class="tc-big-table no-visited is-article-header" data-default-direction="asc">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="false">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-active="false">Name<span class="sorter"></span></th>
          <th data-slug="artist" data-direction="asc" data-active="false">Artist<span class="sorter"></span></th>
          <th data-slug="category" data-direction="asc" data-active="false">Tags<span class="sorter"></span></th>
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
            $postPublicationDate = $post['data']['originalPublicationDate'];
            $thumb = @$post['file']['media']['thumb'];
            $dimensions = DataHelper::getImageDimensions($thumb);
          ?>
          <?php if ($year !== $previousYear): ?>
            <tr class="separator"><td colspan="999"></td></tr>
            <tr class="section"><td></td><td colspan="999"><span class="title"><?= $year; ?></span></td></tr>
          <?php endif; ?>
          <tr class="post orientation-<?= htmlspecialchars($dimensions['orientation']); ?>">
            <td class="right"><span class="inner"><?= htmlentities($pageID); ?></span></td>
            <td><span class="inner"><a href="<?= URL::getURL("/posts/view/{$pageID}"); ?>" class="media">asdf</a></span></td>
            <td><span class="inner">artist</span></td>
            <td class="even-padding">
              <span class="inner">
                <div class="actions narrow">
                  <span class="item blue tag">Tomba</span>
                </div>
              </span>
            </td>
            <td><span class="inner">created</span></td>
          </tr>
          <?php $previousYear = $year; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

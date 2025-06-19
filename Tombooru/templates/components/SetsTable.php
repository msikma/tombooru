<?php
?>
<div class="tc-big-table no-visited is-article-header" data-default-direction="desc" data-is-sortable="false">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="false" data-is-sortable="false">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-default-active="true" data-is-sortable="false">Name<span class="sorter"></span></th>
          <th data-slug="posts" data-direction="asc" data-active="false" data-is-sortable="false">Post IDs<span class="sorter"></span></th>
          <th data-slug="description_page_id" data-direction="asc" data-active="false" data-is-sortable="false" class="icon tiny" <?= Template::setIcon('file-code'); ?> title="Description page ID"><span class="icon"></span><span class="sorter"></span></th>
          <th data-slug="notes_page_id" data-direction="asc" data-active="false" data-is-sortable="false" class="icon tiny" <?= Template::setIcon('tasklist'); ?> title="Notes page ID"><span class="icon"></span><span class="sorter"></span></th>
          <th data-slug="creator" data-direction="asc" data-active="false" data-is-sortable="false" class="icon tiny" <?= Template::setIcon('person'); ?> title="Creator user ID"><span class="icon"></span></th>
          <th data-slug="created_at" data-direction="asc" data-active="false" data-is-sortable="false">Created at<span class="sorter"></span></th>
        </tr>
        <tr class="separator"><td colspan="999"></td></tr>
        <?php if (count($sets) === 0): ?>
          <tr class="notification">
            <td colspan="999">No sets found.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($sets as $set): ?>
          <?php
            $name = trim($set['name']);
            $link = URL::getURL("/sets/view/{$set['id']}");
            $posts = $set['posts'];
            $isPrimary = $set['data']['isPrimary'];
            $descriptionID = empty($set['descriptionPageID']) ? '–' : $set['descriptionPageID'];
            $notesID = empty($set['notesPageID']) ? '–' : $set['notesPageID'];
          ?>
          <tr class="set">
            <td class="right"><span class="inner"><?= $set['id']; ?></span></td>
            <td class="highlighted">
              <span class="inner">
                <a href="<?= htmlspecialchars($link); ?>">
                  <?php if (empty($name)): ?>
                    <?php if ($isPrimary): ?>
                      <em>Primary set</em>
                    <?php else: ?>
                      <em>Unnamed set</em>
                    <?php endif; ?>
                  <?php else: ?>
                    <?= htmlspecialchars($name); ?>
                  <?php endif; ?>
                </a>
              </span>
            </td>
            <td class="even-padding tags"><span class="inner"><div class="actions narrow">
              <?php foreach ($posts as $post): ?>
                <span class="item gray tag"><?= htmlspecialchars($post); ?></span>
              <?php endforeach; ?>
            </div></span></td>
            <td class="minimal id"><span class="inner"><?= htmlspecialchars($descriptionID); ?></span></td>
            <td class="minimal id"><span class="inner"><?= htmlspecialchars($notesID); ?></span></td>
            <td class="creator"><span class="inner"><?= htmlspecialchars($set['creatorUserID']); ?></span></td>
            <td class=""><span class="inner"><?= Template::getComponent('Timestamp', ['ts' => $set['createdAt']]); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

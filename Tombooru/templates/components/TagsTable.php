<div class="tc-big-table no-visited is-article-header" data-default-sort="id">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="true">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-active="false">Name<span class="sorter"></span></th>
          <th data-slug="type" data-direction="asc" data-active="false">Type<span class="sorter"></span></th>
          <th data-slug="type" data-direction="asc" data-active="false">Count<span class="sorter"></span></th>
          <th data-slug="actions" data-direction="asc" data-active="false" data-is-sortable="false">Created at<span class="sorter"></span></th>
        </tr>
        <tr class="separator"><td colspan="999"></td></tr>
        <?php if (count($tags) === 0): ?>
          <tr class="notification">
            <td colspan="999">No results.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($tags as $tag): ?>
          <?php
            $tagName = str_replace('_', ' ', $tag['name']);
            $tagType = $tag['type'];
            $tagLabel = !empty($tagType) ? $tagType : '–';
            $typeIsArtist = $tagType === 'Artist';
            $urlTagView = URL::getTagInfoURL($tag, 'view', $typeIsArtist);
            $urlTagEdit = URL::getTagInfoURL($tag, 'edit', $typeIsArtist);
          ?>
          <tr data-tag-type="<?= htmlentities($tagType); ?>">
            <td class="right highlighted"><span class="inner"><?= htmlentities($tag['id']); ?></span></td>
            <td><span class="inner"><a href="<?= htmlentities($urlTagView); ?>"><?= htmlentities($tagName); ?></a></span></td>
            <td class="even-padding">
              <span class="inner">
                <?= Template::getComponent('TagType', [
                  'tagType' => $tagType,
                  'addWrapper' => true,
                ]); ?>
              </span>
            </td>
            <td><span class="inner"><?= htmlentities($tag['count']); ?></span></td>
            <td><span class="inner"><?= Template::getComponent('Timestamp', ['ts' => $tag['createdAt']]); ?></span></td>
            <td class="tiny control-panel even-padding">
              <span class="inner">
                <div class="actions narrow">
                  <div class="action-sets">
                    <div class="action-set">
                      <a href="<?= htmlentities($urlTagEdit); ?>" class="item icon blue" <?= Template::setIcon('file-code') ?>>Edit</a>
                    </div>
                  </div>
                </div>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
  $apiBaseURL = URL::getURL('/api');
?>
<div class="tc-big-table no-visited is-article-header" data-default-direction="asc" data-default-sort="id"<?= !empty($apiEndpoint) ? ' data-api-endpoint="'.htmlentities($apiEndpoint).'"' : ''; ?> data-api-base-url="<?= htmlentities($apiBaseURL); ?>">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="true">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-active="false">Name<span class="sorter"></span></th>
          <th data-slug="category" data-direction="asc" data-active="false">Category<span class="sorter"></span></th>
          <th data-slug="count" data-direction="asc" data-active="false">Count<span class="sorter"></span></th>
          <th data-slug="created_at" data-direction="asc" data-active="false" data-is-sortable="false">Created at<span class="sorter"></span></th>
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
            $tagCategory = $tag['category'];
            $tagLabel = !empty($tagCategory) ? $tagCategory['name'] : '–';
            $isArtistCategory = DataHelper::isSpecialCategory($tagCategory, 'artist');
            $urlTagView = URL::getTagInfoURL($tag, 'view', $isArtistCategory);
            $urlTagEdit = URL::getTagInfoURL($tag, 'edit', $isArtistCategory);
          ?>
          <tr data-tag-category="<?= htmlentities(!empty($tagCategory['slug']) ? $tagCategory['slug'] : ''); ?>">
            <td class="right highlighted"><span class="inner"><?= htmlentities($tag['id']); ?></span></td>
            <td><span class="inner"><a href="<?= htmlentities($urlTagView); ?>"><?= htmlentities($tagName); ?></a></span></td>
            <td class="even-padding">
              <span class="inner">
                <?= Template::getComponent('TagCategory', [
                  'tagCategory' => @$tagCategory['slug'],
                  'addWrapper' => true,
                ]); ?>
              </span>
            </td>
            <td><span class="inner"><?= htmlentities($tag['count']); ?></span></td>
            <td><span class="inner"><?= Template::getComponent('Timestamp', ['ts' => $tag['createdAt']]); ?></span></td>
            <td class="tiny control-panel even-padding"><span class="inner"><?= Template::getComponent('TagsTableActions', [
              'actions' => [
                'edit' => [
                  'label' => 'Edit',
                  'icon' => 'file-code',
                  'href' => $urlTagEdit,
                ],
              ],
            ]); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

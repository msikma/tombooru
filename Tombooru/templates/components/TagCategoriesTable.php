<div class="tc-big-table no-visited is-article-header" data-default-sort="id">
  <div class="inner">
    <table>
      <tbody>
        <tr class="header">
          <th data-type="number" class="id minimal" data-slug="id" data-direction="asc" data-active="true">#<span class="sorter"></span></th>
          <th data-slug="name" data-direction="asc" data-active="false">Name<span class="sorter"></span></th>
          <th data-slug="icon" data-direction="asc" data-active="false">Icon<span class="sorter"></span></th>
          <th data-slug="color" data-direction="asc" data-active="false">Color<span class="sorter"></span></th>
          <th data-slug="properties" data-direction="asc" data-active="false">Properties<span class="sorter"></span></th>
          <th data-slug="count" data-direction="asc" data-active="false">Count<span class="sorter"></span></th>
          <th data-slug="ordering" data-direction="asc" data-active="false">Ordering<span class="sorter"></span></th>
          <th data-slug="actions" data-direction="asc" data-active="false" data-is-sortable="false">Created at<span class="sorter"></span></th>
        </tr>
        <tr class="separator"><td colspan="999"></td></tr>
        <?php if (count($tagCategories) === 0): ?>
          <tr class="notification">
            <td colspan="999">No results.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($tagCategories as $tagCategory): ?>
          <?php
            $id = $tagCategory['id'];
            $name = $tagCategory['name'];
            $icon = $tagCategory['icon'];
            $color = $tagCategory['color'];
            $properties = $tagCategory['properties'];
            $count = $tagCategory['count'];
            $ordering = $tagCategory['ordering'];
            $createdAt = $tagCategory['createdAt'];
            $urlViewTagCategory = URL::getTagCategoryInfoURL($tagCategory, 'view');
            $urlEditTagCategory = URL::getTagCategoryInfoURL($tagCategory, 'edit');
          ?>
          <tr data-tag-category="<?= htmlentities($name); ?>">
            <td class="right highlighted"><span class="inner"><?= htmlentities($id); ?></span></td>
            <!--
            <td><span class="inner"><a href="<?= htmlentities($urlViewTagCategory); ?>"><?= htmlentities($name); ?></a></span></td>
            -->
            <td><span class="inner"><?= htmlentities($name); ?></span></td>
            <td><span class="inner"><?= htmlentities($icon); ?></span></td>
            <td><span class="inner"><?= htmlentities($color); ?></span></td>
            <td class="even-padding"><span class="inner">
              <div class="actions narrow">
                <div class="action-sets">
                  <div class="action-set">
                    <?php foreach ($properties as $property): ?>
                      <span class="item gray"><?= $property; ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </span></td>
            <td><span class="inner"><?= htmlentities($count); ?></span></td>
            <td><span class="inner"><?= htmlentities($ordering); ?></span></td>
            <td><span class="inner"><?= Template::getComponent('Timestamp', ['ts' => $createdAt]); ?></span></td>
            <!--
            <td class="tiny control-panel even-padding">
              <span class="inner">
                <div class="actions narrow">
                  <div class="action-sets">
                    <div class="action-set">
                      <a href="<?= htmlentities($urlEditTagCategory); ?>" class="item icon blue" <?= Template::setIcon('file-code') ?>>Edit</a>
                    </div>
                  </div>
                </div>
              </span>
            </td>
            -->
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

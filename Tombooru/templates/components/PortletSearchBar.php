<?php ob_start(); ?>
<?php $searchString = @$search['searchString']; ?>
<div class="vector-menu-content-static">
  <form action="<?= URL::getURL('/posts') ?>" data-tombooru-component="SidebarSearchBar">
    <div class="search-input">
      <input
        type="search"
        name="search"
        placeholder="Type to search"
        autocapitalize="off"
        autocomplete="off"
        <?= !empty($searchString) ? ' value="'.htmlentities($searchString).'"' : '' ?>
      />
      <button type="submit">
        Go
      </button>
      <div class="autocomplete-container">
      </div>
    </div>
    <script>Tombooru.decorateComponent()</script>
  </form>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Search',
    'id' => 'side_search_bar',
    'content' => ob_get_clean(),
    'portletClass' => 'blue search-panel',
  ]);
?>

<?php ob_start(); ?>
<?php $searchString = $search; ?>
<div class="vector-menu-content-static">
  <form action="<?= URL::getURL('/tags') ?>" data-tombooru-component="SidebarTagSearchBar">
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
    <!--
    <div class="search-filters">
      <div class="filter select category">
        <select>
            <option disabled selected>Category</option>
        </select>
      </div>
      <div class="filter select order">
        <select>
          <option disabled selected>Order</option>
        </select>
      </div>
      <div class="filter description">
        <label>
          <input type="checkbox" name="has_description" />
          <select>
            <option>With</option>
            <option>Without</option>
          </select> description
        </label>
      </div>
    </div>
    -->
    <script>Tombooru.decorateComponent()</script>
  </form>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Search tags',
    'id' => 'side_tag_search_bar',
    'content' => ob_get_clean(),
    'portletClass' => 'blue search-panel',
  ]);
?>


   <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
   
      <input type="hidden" name="action" value="update_github_settings" />
   
      <h3><?php _e("GitHub Repository Info", "github-api"); ?></h3>
      <p>
      <label><?php _e("GitHub Organization:", "github-api"); ?></label>
      <input class="" type="text" name="gh_org" value="<?php echo get_option('gh_org'); ?>" />
      </p>
   
      <p>
      <label><?php _e("GitHub repository (slug):", "github-api"); ?></label>
      <input class="" type="text" name="gh_repo" value="<?php echo get_option('gh_repo'); ?>" />
      </p>
   
      <input class="button button-primary" type="submit" value="<?php _e("Save", "github-api"); ?>" />
   
   </form>
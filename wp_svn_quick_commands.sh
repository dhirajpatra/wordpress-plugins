# ========================================
# WORDPRESS.ORG SVN UPLOAD - QUICK COMMANDS
# Copy and paste these commands one by one
# ========================================

# STEP 1: Navigate to workspace
cd ~/Desktop/php/

# STEP 2: Checkout SVN repository (enter WordPress.org credentials when prompted)
svn co https://plugins.svn.wordpress.org/neurorag-agent-chatbot neurorag-svn

# STEP 3: Enter SVN directory
cd neurorag-svn

# STEP 4: Copy plugin files to trunk
cp ~/Desktop/php/neurorag-agent-chatbot/neurorag-agent-chatbot.php trunk/
cp ~/Desktop/php/neurorag-agent-chatbot/readme.txt trunk/
cp -r ~/Desktop/php/neurorag-agent-chatbot/css trunk/
cp -r ~/Desktop/php/neurorag-agent-chatbot/js trunk/

# STEP 5: Copy assets (icons, banners, screenshots)
cp ~/Desktop/php/neurorag-agent-chatbot/assets/*.png assets/
cp ~/Desktop/php/neurorag-agent-chatbot/assets/*.svg assets/

# STEP 6: Check what will be uploaded
svn status

# STEP 7: Add new files to SVN
svn add trunk/* --force
svn add assets/* --force

# STEP 8: Check status again (should show 'A' for added files)
svn status

# STEP 9: Commit to SVN (upload to WordPress.org)
svn ci -m "Initial release v1.0.5 - AI-powered chatbot with multi-provider support"

# STEP 10: Create version tag (makes this the stable release)
svn cp trunk tags/1.0.5

# STEP 11: Commit the tag
svn ci -m "Tagging version 1.0.5"

# ========================================
# DONE! Your plugin is now on WordPress.org
# Visit: https://wordpress.org/plugins/neurorag-agent-chatbot/
# (May take 15-30 minutes to appear)
# ========================================


# ========================================
# FUTURE UPDATES - When releasing v1.0.6
# ========================================

# 1. Update your local files first, then:
cd ~/Desktop/php/neurorag-svn

# 2. Update from server
svn up

# 3. Copy updated files
cp ~/Desktop/php/neurorag-agent-chatbot/neurorag-agent-chatbot.php trunk/
cp ~/Desktop/php/neurorag-agent-chatbot/js/chatbot.js trunk/js/
cp ~/Desktop/php/neurorag-agent-chatbot/readme.txt trunk/
cp ~/Desktop/php/neurorag-agent-chatbot/assets/*.* assets/
# Copy other changed files...

# 4. Commit changes
svn ci -m "Update to v1.0.6 - Bug fixes and improvements"

# 5. Create new tag by copy trunk to tag
svn cp trunk/. tags/1.0.6

# Commit new tags
svn ci -m "Tagging version 1.0.6"


# ========================================
# USEFUL SVN COMMANDS
# ========================================

# Check current status
svn status

# See what changed
svn diff

# Update from server
svn up

# Delete a file
svn delete trunk/oldfile.php
svn ci -m "Removed old file"

# Revert local changes
svn revert trunk/file.php

# View commit history
svn log

# See file list on server
svn list https://plugins.svn.wordpress.org/neurorag-agent-chatbot/trunk/

# delete previous tag
# Navigate to your SVN directory
cd ~/Desktop/php/neurorag-svn

# Delete the tag from server
svn delete https://plugins.svn.wordpress.org/neurorag-agent-chatbot/tags/1.0.4 -m "Removing old tag 1.0.4"

# Or delete locally first, then commit
svn delete tags/1.0.4
svn ci -m "Removing tag 1.0.4"

# Check PHP file version
grep "Version:" ~/Desktop/php/neurorag-agent-chatbot/neurorag-agent-chatbot.php

# Check readme.txt stable tag
grep "Stable tag:" ~/Desktop/php/neurorag-agent-chatbot/readme.txt

# 1. Revert the failed commit
svn revert -R .

# 2. Check the current status
svn status

# 3. Remove the incorrectly copied structure
svn rm tags/1.0.5

# 4. Create the tag properly (without the extra "trunk" subdirectory)
svn cp trunk tags/1.0.5

# 5. Commit
svn ci -m "Tagging version 1.0.5"

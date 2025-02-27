<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.tree-list.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('runbooks', $current_folder['id'], $folders_tree, $core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE)) ?>
	</div>
	<div class="content-box">

    <div style="text-align: center;">
        <h1>Random Password Generator</h1>
        <div id="passwords" style="margin: 20px 0; font-family: Courier New; font-size: 14pt; font-weight: bold; color: #333;"></div>
        <button onclick="generatePasswords()" style="padding: 10px 20px; font-size: 1em; border: none; border-radius: 5px; background-color: #007bff; color: #fff; cursor: pointer;">Generate passwords</button>
    </div>

    <script>
        function generatePassword() {
            const lowercase = "abcdefghijkmnopqrstuvwxyz";
            const uppercase = "ABCDEFGHJKLMNPQRSTUVWXYZ";
            const numbers = "23456789";
            const specialChars = "!@#";
            const allChars = lowercase + uppercase + numbers + specialChars;
            let password = "";

            // Ensure at least one character from each category
            password += lowercase[Math.floor(Math.random() * lowercase.length)];
            password += uppercase[Math.floor(Math.random() * uppercase.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += specialChars[Math.floor(Math.random() * specialChars.length)];

            // Fill the rest of the password with random characters
            for (let i = 4; i < 8; i++) {
                password += allChars[Math.floor(Math.random() * allChars.length)];
            }

            // Shuffle the password to ensure randomness
            password = password.split('').sort(() => Math.random() - 0.5).join('');

            return password;
        }

        function generatePasswords() {
            const passwords = [];
            for (let i = 0; i < 10; i++) {
                passwords.push(generatePassword());
            }
            document.getElementById("passwords").innerHTML = passwords.join("<br>");
        }

	generatePasswords();
    </script>
    </div>
    </div>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>

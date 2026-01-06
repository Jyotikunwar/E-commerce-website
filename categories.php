<?php
session_start();

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jyotidb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$feedback_message = '';

// --- HANDLE FORM SUBMISSION TO CREATE A NEW CATEGORY ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;

    $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $description, $parent_id);

    if ($stmt->execute()) {
        $feedback_message = "<div class='success'>New category '$name' was added successfully!</div>";
    } else {
        $feedback_message = "<div class='error'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// --- FETCH ALL CATEGORIES ---
$categories_result = $conn->query("SELECT * FROM categories ORDER BY parent_id, name ASC");
$all_categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $all_categories[] = $row;
    }
}

// --- HELPER FUNCTIONS ---

function hasChildren($categories, $categoryId) {
    foreach ($categories as $category) {
        if ($category['parent_id'] == $categoryId) return true;
    }
    return false;
}

// Function for the visual display list on the right
function displayCategoriesList($categories, $parentId = NULL) {
    $isTopLevel = is_null($parentId);
    if ($isTopLevel) echo "<ul class='category-tree'>";
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $has_children = hasChildren($categories, $category['category_id']);
            $li_class = $has_children ? 'class="has-children"' : '';
            echo "<li {$li_class}><span>" . htmlspecialchars($category['name']) . "</span>";
            if ($has_children) {
                echo "<ul>";
                displayCategoriesList($categories, $category['category_id']);
                echo "</ul>";
            }
            echo "</li>";
        }
    }
    if ($isTopLevel) echo "</ul>";
}

// **UPDATED**: Function to build the interactive list for our custom dropdown
function generateCustomDropdownList($categories, $parentId = NULL) {
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $has_children = hasChildren($categories, $category['category_id']);
            $li_class = $has_children ? 'class="has-children"' : '';

            // Each LI now contains a wrapper for better control
            echo "<li {$li_class} data-value='" . $category['category_id'] . "'>";
            echo "<div class='option-content'>";
            if ($has_children) {
                // The toggle icon is its own element now
                echo "<span class='toggle'></span>";
            }
            // The name is its own element for selection
            echo "<span class='option-name'>" . htmlspecialchars($category['name']) . "</span>";
            echo "</div>";

            if ($has_children) {
                echo "<ul>";
                generateCustomDropdownList($categories, $category['category_id']);
                echo "</ul>";
            }
            echo "</li>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; margin: 0; padding: 40px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .card { background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        h2 { font-weight: 600; color: #004d40; border-bottom: 2px solid #eef0f3; padding-bottom: 10px; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        input[type="text"], textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        .button { width: 100%; padding: 15px; background-color: #4CAF50; border: none; border-radius: 8px; color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .button:hover { background-color: #45a049; }
        .feedback .success { background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        
        /* --- Styles for existing categories list (right side) --- */
        .category-tree, .category-tree ul { list-style-type: none; padding-left: 0; }
        .category-tree li.has-children > span { cursor: pointer; }
        .category-tree li.has-children > span::before { content: '[+]'; margin-right: 8px; color: #4CAF50; }
        .category-tree li > ul { display: none; padding-left: 25px; }
        .category-tree li.open > ul { display: block; }
        .category-tree li.open > span::before { content: '[-]'; }
        .no-categories { color: #777; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px; }
        
        .back-link {
            display: block;
            margin: 20px auto;
            text-align: center;
        }
        .back-link a {
            text-decoration: none;
            padding: 10px 20px;
            background: #09aa6cff;
            color: #fff;
            border-radius: 6px;
            transition: 0.3s;
        }
        .back-link a:hover {
            background: #13f026ff;
        }
        /* --- UPDATED STYLES FOR CUSTOM DROPDOWN --- */
        .custom-select-wrapper { position: relative; }
        .select-selected { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 12px; cursor: pointer; user-select: none; }
        .select-selected::after { position: absolute; content: ""; top: 50%; right: 15px; transform: translateY(-50%); width: 0; height: 0; border: 6px solid transparent; border-color: #555 transparent transparent transparent; }
        .select-selected.select-arrow-active::after { border-color: transparent transparent #555 transparent; top: 40%; }
        .select-items { position: absolute; background-color: #fff; top: 105%; left: 0; right: 0; z-index: 99; border: 1px solid #ddd; border-radius: 8px; max-height: 250px; overflow-y: auto; }
        .select-hide { display: none; }
        .select-items ul, .select-items li { list-style: none; padding-left: 0; margin: 0; }
        .select-items .option-content { display: flex; align-items: center; padding: 10px 15px; }
        .select-items .option-content:hover { background-color: #f1f1f1; }
        .select-items .toggle { width: 20px; height: 20px; cursor: pointer; position: relative; }
        .select-items .toggle::before { content: '[+]'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #4CAF50; font-weight: bold; }
        .select-items li.open > .option-content .toggle::before { content: '[-]'; }
        .select-items .option-name { cursor: pointer; flex-grow: 1; padding-left: 5px; }
        .select-items li > ul { display: none; padding-left: 20px; }
        .select-items li.open > ul { display: block; }
        .select-items .select-option-toplevel { padding: 10px 15px; cursor: pointer; font-style: italic; color: #555; }
        .select-items .select-option-toplevel:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <div class="container">
        <!-- Add Category Form -->
        <div class="card add-category-form">
            <h2>Add New Category</h2>
            <div class="feedback"><?php echo $feedback_message; ?></div>
            <form action="categories.php" method="post">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Parent Category</label>
                    <div class="custom-select-wrapper">
                        <div class="select-selected">-- None (Top-Level Category) --</div>
                        <div class="select-items select-hide">
                            <div class="select-option-toplevel" data-value="">-- None (Top-Level Category) --</div>
                            <ul>
                                <?php generateCustomDropdownList($all_categories); ?>
                            </ul>
                        </div>
                        <input type="hidden" name="parent_id" id="parent_id_hidden" value="">
                    </div>
                </div>
                <button type="submit" name="add_category" class="button">Add Category</button>
            </form>
        </div>

        <!-- Existing Categories List -->
        <div class="card category-list">
            <h2>Existing Categories</h2>
            <?php
            if (!empty($all_categories)) {
                displayCategoriesList($all_categories);
            } else {
                echo '<p class="no-categories">No categories have been added yet.</p>';
            }
            ?>
        </div>
    </div>

    <script>
        // --- SCRIPT FOR RIGHT-SIDE DISPLAY LIST ---
        document.querySelectorAll('.category-tree li.has-children > span').forEach(toggle => {
            toggle.addEventListener('click', function() { this.parentElement.classList.toggle('open'); });
        });

        // --- UPDATED SCRIPT FOR CUSTOM DROPDOWN ---
        const wrapper = document.querySelector('.custom-select-wrapper');
        const selected = wrapper.querySelector('.select-selected');
        const optionsContainer = wrapper.querySelector('.select-items');
        const hiddenInput = wrapper.querySelector('#parent_id_hidden');

        // Open/close the dropdown
        selected.addEventListener('click', () => {
            optionsContainer.classList.toggle('select-hide');
            selected.classList.toggle('select-arrow-active');
        });

        // Handle clicking the "None" option
        optionsContainer.querySelector('.select-option-toplevel').addEventListener('click', function() {
            selected.textContent = this.textContent;
            hiddenInput.value = this.dataset.value;
            optionsContainer.classList.add('select-hide');
        });

        // Handle clicking a toggle icon [+] / [-]
        optionsContainer.querySelectorAll('.toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent the click from bubbling up to the name
                this.closest('li.has-children').classList.toggle('open');
            });
        });

        // Handle clicking a category name to select it
        optionsContainer.querySelectorAll('.option-name').forEach(nameSpan => {
            nameSpan.addEventListener('click', function() {
                const parentLi = this.closest('li');
                selected.textContent = this.textContent;
                hiddenInput.value = parentLi.dataset.value;
                optionsContainer.classList.add('select-hide');
                selected.classList.remove('select-arrow-active');
            });
        });

        // Close the dropdown if clicking outside
        window.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                optionsContainer.classList.add('select-hide');
                selected.classList.remove('select-arrow-active');
            }
        });
    </script>
    
    <div class="back-link">
        <a href="welcome.php">⬅ Back to Home</a>
    </div>

</body>
</html>
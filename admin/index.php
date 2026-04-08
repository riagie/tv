<?php
/**
 * Simple Admin Interface for TV Channels
 * Add, edit, and delete channels from the database
 * Android TV Optimized
 */

// Define base path untuk kompatibilitas di hosting
$path = dirname(__FILE__);
$db = $path . '/../tv.db';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('sqlite:' . $db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO channels (name, url, image, category)
                VALUES (:name, :url, :image, :category)
            ");
            $stmt->execute([
                ':name' => $_POST['name'],
                ':url' => $_POST['url'],
                ':image' => $_POST['image'],
                ':category' => $_POST['category']
            ]);
            $message = '✅ Channel berhasil ditambahkan!';

        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM channels WHERE id = :id");
            $stmt->execute([':id' => $_POST['id']]);
            $message = '✅ Channel berhasil dihapus!';

        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE channels
                SET name = :name, url = :url, image = :image, category = :category
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $_POST['id'],
                ':name' => $_POST['name'],
                ':url' => $_POST['url'],
                ':image' => $_POST['image'],
                ':category' => $_POST['category']
            ]);
            $message = '✅ Channel berhasil diupdate!';
        }

    } catch (PDOException $e) {
        $message = '❌ Error: ' . $e->getMessage();
    }
}

// Fetch all channels
try {
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM channels ORDER BY name ASC");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OGIE NURDIANA</title>
    <meta name="description" content="OGIE NURDIANA - TV Streaming Platform">
    <link rel="icon" type="image/png" href="https://ogienurdiana.com/assets/img/male-technologist.png">
    <link rel="icon" hreflang="en-us" href="https://ogienurdiana.com/assets/img/male-technologist.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000000;
            color: #e5e5e5;
            padding: 20px;
            font-size: 13px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #1f1f1f;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .back-link {
            color: #e5e5e5;
            text-decoration: none;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 16px;
            border: 1px solid #2a2a2a;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            background: #0a0a0a;
        }

        .back-link:focus {
            background: #e5e5e5;
            color: #000000;
            border: 2px solid #e5e5e5;
            outline: none;
            box-shadow: 0 0 0 4px rgba(229, 229, 229, 0.8), 0 0 20px rgba(229, 229, 229, 0.6);
            transform: scale(1.05);
        }

        .message {
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 12px;
        }

        .message.success {
            background: #0a0a0a;
            border: 1px solid #1f1f1f;
        }

        .message.error {
            background: #0a0a0a;
            border: 1px solid #dc3545;
        }

        .add-section {
            background: transparent;
            padding: 16px;
            border: 1px solid #1f1f1f;
            margin-bottom: 20px;
        }

        .add-section h2 {
            margin-bottom: 12px;
            font-size: 12px;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 4px;
            font-size: 11px;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            padding: 8px 12px;
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            color: #e5e5e5;
            font-size: 12px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #e5e5e5;
            box-shadow: 0 0 0 2px rgba(229, 229, 229, 0.3);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(229, 229, 229, 0.4);
        }

        .btn-primary {
            background: transparent;
            color: #e5e5e5;
            border: 1px solid #2a2a2a;
        }

        .btn-primary:hover {
            background: #0a0a0a;
        }

        .btn-primary:focus {
            background: #e5e5e5;
            color: #000000;
            border: 2px solid #e5e5e5;
            outline: none;
            box-shadow: 0 0 0 4px rgba(229, 229, 229, 0.8), 0 0 20px rgba(229, 229, 229, 0.6);
            transform: scale(1.05);
        }

        .btn-danger {
            background: transparent;
            color: #dc3545;
            border: 1px solid transparent;
            padding: 4px 8px;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            text-decoration: underline;
        }

        .btn-danger:focus {
            background: #dc3545;
            color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.4);
        }

        .btn-edit {
            background: transparent;
            color: #0a84ff;
            border: 1px solid transparent;
            padding: 4px 8px;
            transition: all 0.2s ease;
        }

        .btn-edit:hover {
            text-decoration: underline;
        }

        .btn-edit:focus {
            background: #0a84ff;
            color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.4);
        }

        .btn-cancel {
            background: transparent;
            color: #6b6b6b;
            border: 1px solid #2a2a2a;
        }

        .btn-cancel:hover {
            background: #0a0a0a;
            color: #e5e5e5;
        }

        .btn-cancel:focus {
            background: #6b6b6b;
            color: #000000;
            border-color: #6b6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(107, 107, 107, 0.5);
        }

        .btn-small {
            font-size: 10px;
            padding: 4px 8px;
            background: transparent;
            color: #e5e5e5;
            border: 1px solid #2a2a2a;
            transition: all 0.2s ease;
        }

        .btn-small:hover {
            background: #0a0a0a;
        }

        .btn-small:focus {
            background: #e5e5e5;
            color: #000000;
            border-color: #e5e5e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(229, 229, 229, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            border: 1px solid #1f1f1f;
        }

        th {
            background: #0a0a0a;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #1f1f1f;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #1f1f1f;
            font-size: 12px;
        }

        tr:hover td {
            background: #0a0a0a;
        }

        tr:focus td {
            background: #141414;
            outline: none;
        }

        tr:focus-visible {
            outline: 2px solid #e5e5e5;
            outline-offset: -2px;
        }

        .channel-logo {
            width: 60px;
            height: 34px;
            object-fit: contain;
        }

        .category-badge {
            display: inline-block;
            padding: 2px 6px;
            background: #1a1a1a;
            font-size: 9px;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .actions {
            display: flex;
            gap: 4px;
        }

        .link-url {
            color: #6b6b6b;
            text-decoration: none;
            font-size: 10px;
        }

        .link-url:hover {
            color: #e5e5e5;
        }

        .channel-count {
            margin-bottom: 16px;
            font-size: 11px;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Android TV Focus Styles */
        *:focus-visible {
            outline: 3px solid #e5e5e5;
            outline-offset: 2px;
        }

        /* Override focus-visible for buttons with custom focus styles */
        .btn:focus-visible,
        .back-link:focus-visible {
            outline: none;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 16px;
            }

            th, td {
                padding: 8px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Channels</h1>
            <a href="../index.php" class="back-link" id="backLink">← Kembali</a>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="channel-count"><?php echo count($channels); ?> channel</div>

        <!-- Add Channel Form -->
        <div class="add-section" id="formSection">
            <h2 id="formTitle">Tambah Channel</h2>
            <form method="POST" id="channelForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" id="channelId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="url">URL Stream</label>
                        <input type="url" id="url" name="url" required>
                    </div>
                    <div class="form-group">
                        <label for="image">URL Logo</label>
                        <input type="url" id="image" name="image" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category">
                            <option value="entertainment">Entertainment</option>
                            <option value="news">News</option>
                            <option value="business">Business</option>
                            <option value="sports">Sports</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 12px;">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Tambah</button>
                    <button type="button" class="btn btn-cancel" id="cancelBtn" style="display: none;">Batal</button>
                </div>
            </form>
        </div>

        <!-- Channels List -->
        <table>
            <thead>
                <tr>
                    <th width="60">Logo</th>
                    <th>Nama</th>
                    <th width="100">Kategori</th>
                    <th>URL</th>
                    <th width="80">Aksi</th>
                </tr>
            </thead>
            <tbody id="channelsTable">
                <?php
                $rowIndex = 0;
                foreach ($channels as $channel):
                ?>
                <tr data-index="<?php echo $rowIndex++; ?>">
                    <td>
                        <img src="<?php echo htmlspecialchars($channel['image']); ?>"
                             alt="<?php echo htmlspecialchars($channel['name']); ?>"
                             class="channel-logo"
                             onerror="this.style.display='none'">
                    </td>
                    <td><?php echo htmlspecialchars($channel['name']); ?></td>
                    <td>
                        <span class="category-badge">
                            <?php echo htmlspecialchars($channel['category']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo htmlspecialchars($channel['url']); ?>"
                           target="_blank"
                           class="link-url">
                            Link
                        </a>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button"
                                    class="btn btn-edit"
                                    onclick="editChannel(<?php echo $channel['id']; ?>, '<?php echo htmlspecialchars($channel['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($channel['url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($channel['image'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($channel['category'], ENT_QUOTES); ?>')">
                                Edit
                            </button>
                            <form method="POST" class="deleteForm" data-id="<?php echo htmlspecialchars($channel['name']); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $channel['id']; ?>">
                                <button type="submit"
                                        class="btn btn-danger"
                                        data-channel="<?php echo htmlspecialchars($channel['name']); ?>">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Edit Channel Function
        function editChannel(id, name, url, image, category) {
            // Populate form with channel data
            document.getElementById('channelId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('url').value = url;
            document.getElementById('image').value = image;
            document.getElementById('category').value = category;

            // Change form mode to update
            document.querySelector('input[name="action"]').value = 'update';
            document.getElementById('formTitle').textContent = 'Edit Channel';
            document.getElementById('submitBtn').textContent = 'Update';
            document.getElementById('cancelBtn').style.display = 'inline-block';

            // Focus first input
            document.getElementById('name').focus();

            // Scroll to form
            document.getElementById('formSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Cancel Edit Function
        function cancelEdit() {
            // Reset form
            document.getElementById('channelForm').reset();
            document.getElementById('channelId').value = '';
            document.querySelector('input[name="action"]').value = 'add';
            document.getElementById('formTitle').textContent = 'Tambah Channel';
            document.getElementById('submitBtn').textContent = 'Tambah';
            document.getElementById('cancelBtn').style.display = 'none';

            // Focus first input
            document.getElementById('name').focus();
        }

        // Cancel button event listener
        document.getElementById('cancelBtn').addEventListener('click', cancelEdit);

        // Android TV Keyboard Navigation - Simplified Sequential Focus
        let focusableElements = [];
        let currentIndex = 0;

        function initFocusableElements() {
            // Collect all focusable elements in order
            focusableElements = Array.from(document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(el => {
                // Filter out elements inside table rows (we'll handle those separately)
                return !el.closest('tbody tr');
            });

            // Add table rows
            const tableRows = Array.from(document.querySelectorAll('#channelsTable tr'));
            tableRows.forEach(row => {
                row.tabIndex = 0;
                focusableElements.push(row);
            });
        }

        function getCurrentElement() {
            return focusableElements[currentIndex] || null;
        }

        function focusIndex(index) {
            if (index >= 0 && index < focusableElements.length) {
                currentIndex = index;
                const element = focusableElements[currentIndex];
                if (element) {
                    element.focus();
                }
            }
        }

        document.addEventListener('keydown', function(e) {
            const activeElement = document.activeElement;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    // Move to next element
                    focusIndex(currentIndex + 1);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    // Move to previous element
                    focusIndex(currentIndex - 1);
                    break;

                case 'ArrowRight':
                    e.preventDefault();
                    // Navigate: Table row → Edit button → Delete button
                    if (activeElement.tagName === 'TR') {
                        const editBtn = activeElement.querySelector('.btn-edit');
                        if (editBtn) {
                            editBtn.focus();
                        }
                    } else if (activeElement.classList.contains('btn-edit')) {
                        // From edit button, move to delete button
                        const row = activeElement.closest('tr');
                        if (row) {
                            const deleteBtn = row.querySelector('.btn-danger');
                            if (deleteBtn) {
                                deleteBtn.focus();
                            }
                        }
                    }
                    break;

                case 'ArrowLeft':
                    e.preventDefault();
                    // Navigate: Delete button → Edit button → Table row
                    if (activeElement.classList.contains('btn-danger')) {
                        // From delete button, move to edit button
                        const row = activeElement.closest('tr');
                        if (row) {
                            const editBtn = row.querySelector('.btn-edit');
                            if (editBtn) {
                                editBtn.focus();
                            }
                        }
                    } else if (activeElement.classList.contains('btn-edit')) {
                        // From edit button, return to table row
                        const row = activeElement.closest('tr');
                        if (row) {
                            row.focus();
                        }
                    }
                    break;

                case 'Enter':
                    // Handle activation
                    if (activeElement) {
                        if (activeElement.tagName === 'TR') {
                            // Enter on row focuses edit button
                            e.preventDefault();
                            const editBtn = activeElement.querySelector('.btn-edit');
                            if (editBtn) {
                                editBtn.focus();
                            }
                        } else if (activeElement.classList.contains('btn-danger')) {
                            // Confirm delete
                            e.preventDefault();
                            const confirmed = confirm('Hapus channel ini?');
                            if (confirmed) {
                                activeElement.click();
                            }
                        } else if (activeElement.tagName === 'BUTTON' || activeElement.tagName === 'A') {
                            // Let default behavior work for buttons/links
                        }
                    }
                    break;

                case 'Escape':
                    e.preventDefault();
                    // If in edit mode, cancel edit
                    if (document.getElementById('cancelBtn').style.display !== 'none') {
                        cancelEdit();
                    } else {
                        // Return to first input and clear form
                        const nameInput = document.getElementById('name');
                        if (nameInput) {
                            nameInput.focus();
                            nameInput.value = '';
                            const urlInput = document.getElementById('url');
                            const imageInput = document.getElementById('image');
                            const categorySelect = document.getElementById('category');
                            if (urlInput) urlInput.value = '';
                            if (imageInput) imageInput.value = '';
                            if (categorySelect) categorySelect.selectedIndex = 0;
                        }
                    }
                    break;
            }
        });

        // Initialize on page load
        window.addEventListener('load', function() {
            initFocusableElements();

            // Focus first input
            const nameInput = document.getElementById('name');
            if (nameInput) {
                nameInput.focus();
            }
        });

        // Update focusable elements after form submission
        document.addEventListener('submit', function() {
            setTimeout(() => {
                initFocusableElements();
                const nameInput = document.getElementById('name');
                if (nameInput) {
                    nameInput.focus();
                }
                // Reset form mode after successful submission
                cancelEdit();
            }, 100);
        });

        // Scroll element into view when focused
        function scrollToElement(element) {
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Override focus to include scroll
        const originalFocus = HTMLElement.prototype.focus;
        HTMLElement.prototype.focus = function() {
            originalFocus.apply(this, arguments);
            scrollToElement(this);
        };
    </script>
</body>
</html>

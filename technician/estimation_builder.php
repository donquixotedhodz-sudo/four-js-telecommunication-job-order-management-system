<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch technician details
    $stmt = $pdo->prepare("SELECT name, profile_picture FROM technicians WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch AC parts by category
    $stmt = $pdo->prepare("SELECT * FROM ac_parts ORDER BY part_category, part_name");
    $stmt->execute();
    $allParts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group parts by category
    $partsByCategory = [];
    foreach ($allParts as $part) {
        $partsByCategory[$part['part_category']][] = $part;
    }

    // Fetch cleaning services
    $stmt = $pdo->prepare("SELECT * FROM cleaning_services ORDER BY service_name");
    $stmt->execute();
    $cleaningServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch aircon HP for cleaning service pricing
    $stmt = $pdo->prepare("SELECT * FROM aircon_hp ORDER BY hp ASC");
    $stmt->execute();
    $airconHPs = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<style>
.components-panel {
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    height: calc(100vh - 200px);
    overflow-y: auto;
}

.estimation-panel-wrapper {
    position: sticky;
    top: 20px;
    height: fit-content;
    max-height: calc(100vh - 200px);
}

.estimation-panel {
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
}

.selected-items-wrapper {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    background-color: #f8f9fa;
}

.component-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.component-card:hover {
    border-color: #4A90E2;
    box-shadow: 0 2px 10px rgba(74, 144, 226, 0.1);
    transform: translateY(-2px);
}

.component-card.selected {
    border-color: #4A90E2;
    background-color: #f8f9ff;
}

.category-header {
    background: linear-gradient(135deg, #4A90E2, #5C9CE6);
    color: white;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-weight: 600;
}

.estimation-summary {
    border-top: 2px solid #4A90E2;
    padding-top: 20px;
    margin-top: 20px;
}

.selected-item {
    background: #f8f9ff;
    border: 1px solid #4A90E2;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
    position: relative;
}

.remove-item {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    font-size: 12px;
    cursor: pointer;
}

.price-display {
    font-size: 1.2em;
    font-weight: bold;
    color: #28a745;
}

.total-price {
    font-size: 1.5em;
    font-weight: bold;
    color: #4A90E2;
    border-top: 2px solid #4A90E2;
    padding-top: 15px;
    margin-top: 15px;
}

.search-box {
    border-radius: 10px;
    border: 1px solid #ddd;
    padding: 10px 15px;
    margin-bottom: 20px;
}

.tab-content {
    padding: 0;
}

.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 12px 20px;
}

.nav-tabs .nav-link.active {
    background-color: transparent;
    border-bottom: 3px solid #4A90E2;
    color: #4A90E2;
    font-weight: 600;
}

/* New Service HP Card Styling */
.service-hp-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #ffffff;
}

.service-hp-card:hover {
    border-color: #4A90E2;
    box-shadow: 0 2px 8px rgba(74, 144, 226, 0.15);
    transform: translateY(-1px);
}

.service-hp-card .service-title {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.service-hp-card .hp-spec {
    color: #6c757d;
    font-weight: 500;
    text-transform: uppercase;
}

.service-hp-card .warranty-badge .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    background-color: #6c757d !important;
}

.service-hp-card .price-section {
    text-align: right;
}

.service-hp-card .price-main {
    font-size: 1.25rem;
    font-weight: 700;
    color: #28a745;
    line-height: 1.2;
}

.service-hp-card:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(74, 144, 226, 0.2);
}
</style>

<body>
    <div class="wrapper">
        <?php require_once 'includes/sidebar.php'; ?>

        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                     alt="Technician" 
                                     class="rounded-circle me-2" 
                                     width="32" 
                                     height="32"
                                     style="object-fit: cover; border: 2px solid #4A90E2;">
                                <span class="me-3">Welcome, <?= htmlspecialchars($technician['name'] ?: 'Technician') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2" href="profile.php">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="../admin/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <h3 class="mb-3">AC Service Estimation Builder</h3>
                <p class="text-muted mb-4">Build comprehensive estimates for AC services and repairs</p>



                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <!-- Components Panel (Left Side) -->
                            <div class="col-lg-8">
                                <div class="components-panel p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Components & Services</h5>
                                <input type="text" class="form-control search-box" style="width: 300px;" placeholder="Search components..." id="searchBox">
                            </div>

                            <!-- Tabs for different categories -->
                            <ul class="nav nav-tabs" id="componentTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="parts-tab" data-bs-toggle="tab" data-bs-target="#parts" type="button" role="tab">
                                        <i class="fas fa-cog me-2"></i>AC Parts
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                                        <i class="fas fa-tools me-2"></i>Cleaning Services
                                    </button>
                                </li>

                            </ul>

                            <div class="tab-content" id="componentTabContent">
                                <!-- AC Parts Tab -->
                                <div class="tab-pane fade show active" id="parts" role="tabpanel">
                                    <?php foreach ($partsByCategory as $category => $parts): ?>
                                        <div class="category-header">
                                            <i class="fas fa-wrench me-2"></i><?= ucwords(str_replace('_', ' ', $category)) ?>
                                        </div>
                                        <?php foreach ($parts as $part): ?>
                                            <div class="component-card p-3" data-part='<?= json_encode($part) ?>' onclick="addPart(JSON.parse(this.dataset.part))">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($part['part_name']) ?></h6>
                                                        <p class="text-muted mb-1 small"><?= htmlspecialchars($part['part_code']) ?></p>
                                                        <div class="d-flex gap-2 mb-2">
                                            <span class="badge bg-secondary"><?= $part['warranty_months'] ?> months warranty</span>
                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="price-display">₱<?= number_format($part['unit_price'] + $part['labor_cost'], 2) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Cleaning Services Tab -->
                                <div class="tab-pane fade" id="services" role="tabpanel">
                                    <div class="category-header">
                                        <i class="fas fa-spray-can me-2"></i>Cleaning Services
                                    </div>
                                    <?php foreach ($cleaningServices as $service): ?>
                                        <?php foreach ($airconHPs as $hp): ?>
                                            <div class="card mb-3 service-hp-card shadow-sm" 
                                                 data-service-id="<?= $service['id'] ?>" 
                                                 data-hp-id="<?= $hp['id'] ?>"
                                                 data-service-name="<?= htmlspecialchars($service['service_name']) ?>"
                                                 data-hp="<?= $hp['hp'] ?>"
                                                 data-price="<?= $hp['price'] ?>"
                                                 onclick="addServiceWithSpecificHP(this)">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 service-title"><?= htmlspecialchars($service['service_name']) ?></h6>
                                                            <p class="text-muted mb-0 small hp-spec"><?= $hp['hp'] ?>HP</p>
                                                            <div class="warranty-badge mt-1">
                                                                <span class="badge bg-secondary">6 months warranty</span>
                                                            </div>
                                                        </div>
                                                        <div class="text-end price-section">
                                                            <div class="price-main">₱<?= number_format($hp['price'], 2) ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>


                            </div>
                        </div>
                    </div>

                    <!-- Estimation Panel (Right Side) -->
                    <div class="col-lg-4">
                        <div class="estimation-panel-wrapper">
                            <div class="estimation-panel p-4">
                                <h5 class="mb-4"><i class="fas fa-receipt me-2"></i>Estimation Details</h5>
                                
                                <!-- Selected Items -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="text-muted mb-0">Selected Items</h6>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearEstimation()">
                                            <i class="fas fa-trash me-1"></i>Clear All
                                        </button>
                                    </div>
                                    <div class="selected-items-wrapper">
                                        <div id="selectedItems">
                                            <p class="text-muted text-center">No items selected</p>
                                        </div>
                                    </div>
                                </div>

                            <!-- Estimation Summary -->
                            <div class="estimation-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Parts Subtotal:</span>
                                    <span id="partsSubtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Labor Subtotal:</span>
                                    <span id="laborSubtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Services Subtotal:</span>
                                    <span id="servicesSubtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Additional Fees:</span>
                                    <input type="number" class="form-control form-control-sm" id="additionalFees" value="0" style="width: 100px; text-align: right;" oninput="updateTotal()">
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Discount (₱):</span>
                                    <input type="number" class="form-control form-control-sm" id="discountAmount" value="0" min="0" style="width: 100px; text-align: right;" oninput="updateTotal()">
                                </div>
                                <div class="total-price d-flex justify-content-between">
                                    <span>Total Amount:</span>
                                    <span id="totalAmount">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    
    <script>
        let selectedItems = [];
        let partsTotal = 0;
        let laborTotal = 0;
        let servicesTotal = 0;

        function addPart(part) {
            const existingIndex = selectedItems.findIndex(item => item.type === 'part' && item.id === part.id);
            
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity += 1;
            } else {
                selectedItems.push({
                    type: 'part',
                    id: part.id,
                    name: part.part_name,
                    code: part.part_code,
                    unitPrice: parseFloat(part.unit_price),
                    laborCost: parseFloat(part.labor_cost),
                    quantity: 1,
                    warranty: part.warranty_months
                });
            }
            
            updateEstimation();
        }

        function addService(service) {
            const existingIndex = selectedItems.findIndex(item => item.type === 'service' && item.id === service.id);
            
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity += 1;
            } else {
                selectedItems.push({
                    type: 'service',
                    id: service.id,
                    name: service.service_name,
                    description: service.service_description,
                    price: 0, // Default price since base_price column doesn't exist
                    quantity: 1
                });
            }
            
            updateEstimation();
        }

        function addServiceWithSpecificHP(cardElement) {
            const serviceId = cardElement.getAttribute('data-service-id');
            const hpId = cardElement.getAttribute('data-hp-id');
            const serviceName = cardElement.getAttribute('data-service-name');
            const hp = cardElement.getAttribute('data-hp');
            const price = parseFloat(cardElement.getAttribute('data-price'));
            
            const existingIndex = selectedItems.findIndex(item => 
                item.type === 'service' && 
                item.id === serviceId && 
                item.hp === hp
            );
            
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity += 1;
            } else {
                selectedItems.push({
                    type: 'service',
                    id: serviceId,
                    name: serviceName,
                    description: `${serviceName} for ${hp}HP AC unit`,
                    hp: hp,
                    price: price,
                    quantity: 1
                });
            }
            
            updateEstimation();
        }

        function addServiceWithHP(button) {
            const card = button.closest('.component-card');
            const service = JSON.parse(card.dataset.service);
            const hpSelect = card.querySelector('.hp-select');
            
            if (!hpSelect.value) {
                alert('Please select an HP rating first');
                return;
            }
            
            const selectedOption = hpSelect.options[hpSelect.selectedIndex];
            const hp = selectedOption.getAttribute('data-hp');
            const price = parseFloat(selectedOption.getAttribute('data-price'));
            
            const existingIndex = selectedItems.findIndex(item => 
                item.type === 'service' && 
                item.id === service.id && 
                item.hp === hp
            );
            
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity += 1;
            } else {
                selectedItems.push({
                    type: 'service',
                    id: service.id,
                    name: service.service_name,
                    description: service.service_description,
                    hp: hp,
                    price: price,
                    quantity: 1
                });
            }
            
            // Reset the HP selection
            hpSelect.value = '';
            
            updateEstimation();
        }



        function removeItem(index) {
            selectedItems.splice(index, 1);
            updateEstimation();
        }

        function updateQuantity(index, newQuantity) {
            if (newQuantity <= 0) {
                removeItem(index);
            } else {
                selectedItems[index].quantity = parseInt(newQuantity);
                updateEstimation();
            }
        }

        function updateEstimation() {
            const selectedItemsDiv = document.getElementById('selectedItems');
            
            if (selectedItems.length === 0) {
                selectedItemsDiv.innerHTML = '<p class="text-muted text-center">No items selected</p>';
                partsTotal = laborTotal = servicesTotal = 0;
            } else {
                let html = '';
                partsTotal = laborTotal = servicesTotal = 0;
                
                selectedItems.forEach((item, index) => {
                    if (item.type === 'part') {
                        const itemPartsTotal = item.unitPrice * item.quantity;
                        const itemLaborTotal = item.laborCost * item.quantity;
                        partsTotal += itemPartsTotal;
                        laborTotal += itemLaborTotal;
                        
                        html += `
                            <div class="selected-item">
                                <button class="remove-item" onclick="removeItem(${index})">&times;</button>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>${item.name}</strong><br>
                                        <small class="text-muted">${item.code}</small>
                                    </div>
                                    <div class="text-end">
                                        <input type="number" class="form-control form-control-sm" value="${item.quantity}" min="1" style="width: 60px;" onchange="updateQuantity(${index}, this.value)">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small>Parts: ₱${itemPartsTotal.toFixed(2)}<br>Labor: ₱${itemLaborTotal.toFixed(2)}</small>
                                    <strong>₱${(itemPartsTotal + itemLaborTotal).toFixed(2)}</strong>
                                </div>
                            </div>
                        `;
                    } else if (item.type === 'service') {
                        const itemTotal = item.price * item.quantity;
                        servicesTotal += itemTotal;
                        
                        html += `
                            <div class="selected-item">
                                <button class="remove-item" onclick="removeItem(${index})">&times;</button>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>${item.name}</strong><br>
                                        <small class="text-muted">${item.description}</small>
                                        ${item.hp ? `<br><small class="text-info">${item.hp} HP</small>` : ''}
                                    </div>
                                    <div class="text-end">
                                        <input type="number" class="form-control form-control-sm" value="${item.quantity}" min="1" style="width: 60px;" onchange="updateQuantity(${index}, this.value)">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small>Service${item.hp ? ` (${item.hp} HP)` : ''}</small>
                                    <strong>₱${itemTotal.toFixed(2)}</strong>
                                </div>
                            </div>
                        `;
                    }
                });
                
                selectedItemsDiv.innerHTML = html;
            }
            
            updateTotal();
        }

        function updateTotal() {
            document.getElementById('partsSubtotal').textContent = `₱${partsTotal.toFixed(2)}`;
            document.getElementById('laborSubtotal').textContent = `₱${laborTotal.toFixed(2)}`;
            document.getElementById('servicesSubtotal').textContent = `₱${servicesTotal.toFixed(2)}`;
            
            const additionalFees = parseFloat(document.getElementById('additionalFees').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            
            const subtotal = partsTotal + laborTotal + servicesTotal + additionalFees;
            const total = subtotal - discountAmount;
            
            document.getElementById('totalAmount').textContent = `₱${total.toFixed(2)}`;
        }

        function clearEstimation() {
            if (confirm('Are you sure you want to clear all selected items?')) {
                selectedItems = [];
                document.getElementById('additionalFees').value = '0';
                document.getElementById('discountAmount').value = '0';
                updateEstimation();
            }
        }



        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const activeTab = document.querySelector('.tab-pane.active');
            const componentCards = activeTab.querySelectorAll('.component-card');
            
            componentCards.forEach(card => {
                const name = card.querySelector('h6').textContent.toLowerCase();
                const description = card.querySelector('.text-muted') ? card.querySelector('.text-muted').textContent.toLowerCase() : '';
                
                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Include sidebar functionality from external file
        
        // Debug sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking sidebar elements...');
            const sidebarToggle = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            console.log('sidebarToggle:', sidebarToggle);
            console.log('sidebar:', sidebar);
            console.log('content:', content);
            
            if (sidebarToggle && sidebar && content) {
                console.log('All elements found, adding click listener...');
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Hamburger clicked!');
                    sidebar.classList.toggle('active');
                    content.classList.toggle('expanded');
                    console.log('Sidebar classes:', sidebar.className);
                    console.log('Content classes:', content.className);
                });
            } else {
                console.log('Missing elements - cannot initialize sidebar toggle');
            }
        });
    </script>
    
    <!-- Sidebar functionality -->
    <script src="../js/sidebar.js"></script>
</body>
</html>
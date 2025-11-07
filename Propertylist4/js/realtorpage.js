document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    function switchTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Show the selected tab content
        document.getElementById(tabId).classList.add('active');
        
        // Update tab buttons
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
    }
    
    // Make the function available globally
    window.switchTab = switchTab;
    
    // Initialize the first tab as active
    switchTab('upload');
    
    // Other functions...
    window.resetForm = function() {
        document.getElementById('property-form').reset();
        document.querySelector('.alert').style.display = 'none';
    };
    
    window.openReportModal = function() {
        document.getElementById('report-modal').classList.add('show');
    };
    
    window.closeReportModal = function() {
        document.getElementById('report-modal').classList.remove('show');
    };
    
    window.editProperty = function() {
        alert('Edit property functionality goes here.');
    };
    
    window.deleteProperty = function() {
        alert('Delete property functionality goes here.');
    };
    
    // Address autocomplete functionality
    const addressInput = document.getElementById('property-address');
    const suggestionsContainer = document.getElementById('address-suggestions');
    let debounceTimer;
    
    addressInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();
        
        if (query.length < 3) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(function() {
            fetchAddressSuggestions(query);
        }, 300);
    });
    
    function fetchAddressSuggestions(query) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    suggestionsContainer.innerHTML = '';
                    data.forEach(item => {
                        const suggestion = document.createElement('div');
                        suggestion.className = 'address-suggestion';
                        suggestion.textContent = item.display_name;
                        suggestion.addEventListener('click', function() {
                            addressInput.value = item.display_name;
                            suggestionsContainer.style.display = 'none';
                        });
                        suggestionsContainer.appendChild(suggestion);
                    });
                    suggestionsContainer.style.display = 'block';
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching address suggestions:', error);
                suggestionsContainer.style.display = 'none';
            });
    }
});

document.getElementById('property-images').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('image-preview');
    previewContainer.innerHTML = ''; // Clear previous previews
    
    const files = e.target.files;
    const maxFiles = 10;
    
    // Limit to 10 files
    if (files.length > maxFiles) {
        alert(`You can only upload up to ${maxFiles} images`);
        this.value = ''; // Clear the input
        return;
    }
    
    // Check each file size
    for (let i = 0; i < files.length; i++) {
        if (files[i].size > 5 * 1024 * 1024) { // 5MB
            alert(`File "${files[i].name}" is too large (max 5MB)`);
            this.value = ''; // Clear the input
            previewContainer.innerHTML = '';
            return;
        }
    }
    
    // Create previews for each selected file
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.match('image.*')) continue;
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'image-preview';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-image';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = function() {
                previewDiv.remove();
                removeFileFromInput(file);
            };
            
            previewDiv.appendChild(img);
            previewDiv.appendChild(removeBtn);
            previewContainer.appendChild(previewDiv);
        };
        
        reader.readAsDataURL(file);
    }
});

// Helper function to remove a file from the input
function removeFileFromInput(fileToRemove) {
    const input = document.getElementById('property-images');
    const files = Array.from(input.files);
    const index = files.findIndex(file => 
        file.name === fileToRemove.name && 
        file.size === fileToRemove.size && 
        file.lastModified === fileToRemove.lastModified
    );
    
    if (index !== -1) {
        files.splice(index, 1);
        
        // Create a new DataTransfer to update the files
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    }
}

// Add drag and drop functionality
const fileUpload = document.querySelector('.file-upload');
const fileInput = document.getElementById('property-images');

fileUpload.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUpload.classList.add('dragover');
});

fileUpload.addEventListener('dragleave', () => {
    fileUpload.classList.remove('dragover');
});

fileUpload.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUpload.classList.remove('dragover');
    
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        // Trigger the change event manually
        const event = new Event('change');
        fileInput.dispatchEvent(event);
    }
});


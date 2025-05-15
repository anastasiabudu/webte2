let currentLaureateId = null;
let dataTable; // Переменная для хранения экземпляра DataTable




// Загрузка данных при открытии страницы
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');

    if (editId) {
        openEditModal(editId); // Открываем модальное окно для редактирования, если есть параметр edit
    } else {
        fetchLaureates(); // Иначе загружаем таблицу
    }
});

// Получение списка лауреатов
async function fetchLaureates() {
    try {
        const response = await fetch('https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        initializeDataTable(data); // Инициализируем DataTable с данными
    } catch (error) {
        console.error('Error fetching laureates:', error);
        alert('Failed to fetch laureates. Please try again later.');
    }
}

// Инициализация DataTable
function initializeDataTable(data) {
    if (dataTable) {
        dataTable.destroy(); // Уничтожаем существующую таблицу, если она есть
    }

    dataTable = $('#laureates-table').DataTable({
        data: data,
        columns: [
            { data: 'fullname', title: 'Name' },
            { data: 'country', title: 'Country' },
            { data: 'year', title: 'Year' },
            { data: 'category', title: 'Category' }
        ],
        paging: true, // Включаем пагинацию
        pageLength: 10, // Количество строк на странице
        lengthMenu: [10, 25, 50, 100], // Опции для выбора количества строк
        searching: true, // Включаем поиск
        ordering: true, // Включаем сортировку
        info: true, // Показываем информацию о страницах
        responsive: true // Включаем адаптивность
    });

    // Обработка клика на строку
    $('#laureates-table tbody').on('click', 'tr', function () {
        const data = dataTable.row(this).data();
        window.location.href = `laureate-details.html?id=${data.laureate_id}`;
    });
}

// Открытие модального окна для добавления
function openAddModal() {

    currentLaureateId = null; // Сбрасываем ID
    document.getElementById('add-edit-modal-title').textContent = 'Add Laureate';
    document.getElementById('laureate-form').reset();
    document.getElementById('add-edit-modal').style.display = 'flex';
}

// Открытие модального окна для редактирования
async function openEditModal(id) {
    currentLaureateId = id;
    try {
        const response = await fetch(`https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates/${id}`); // Без слеша
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Fetched laureate data:', data);

        document.getElementById('add-edit-modal-title').textContent = 'Edit Laureate';
        document.getElementById('fullname').value = data[0].fullname;
        document.getElementById('sex').value = data[0].sex;
        document.getElementById('birth_year').value = data[0].birth_year;
        document.getElementById('death_year').value = data[0].death_year || '';
        document.getElementById('country').value = data[0].country || '';
        document.getElementById('year').value = data[0].year;
        document.getElementById('category').value = data[0].category;
        document.getElementById('contribution_sk').value = data[0].contribution_sk || '';
        document.getElementById('contribution_en').value = data[0].contribution_en || '';

        document.getElementById('add-edit-modal').style.display = 'flex';
    } catch (error) {
        console.error('Error fetching laureate details:', error);
        alert('Failed to fetch laureate details. Please try again later.');
    }
}

// Закрытие модального окна
function closeAddEditModal() {
    document.getElementById('add-edit-modal').style.display = 'none';
}

// Обработка формы добавления/редактирования
document.getElementById('laureate-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        fullname: document.getElementById('fullname').value,
        sex: document.getElementById('sex').value,
        birth_year: parseInt(document.getElementById('birth_year').value),
        death_year: document.getElementById('death_year').value || null,
        country: document.getElementById('country').value,
        year: parseInt(document.getElementById('year').value),
        category: document.getElementById('category').value,
        contribution_sk: document.getElementById('contribution_sk').value || '',
        contribution_en: document.getElementById('contribution_en').value || '',
        organisation: document.getElementById('organisation').value || null
    };

    console.log('Form data to be sent:', formData);

    const url = currentLaureateId 
        ? `https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates/${currentLaureateId}` // Редактирование
        : 'https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates'; // Добавление

    const method = currentLaureateId ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const responseText = await response.text();
        console.log('Server response:', responseText);

        if (!response.ok) {
            if (!responseText) {
                throw new Error(`HTTP error! status: ${response.status}, no response body`);
            }

            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                throw new Error(`HTTP error! status: ${response.status}, invalid JSON response`);
            }

            if (errorData.data && errorData.data.code === '23000') {
                alert(`Error: A laureate with the name "${formData.fullname}" already exists.`);
            } else {
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message}`);
            }
            return;
        }

        console.log('Laureate saved/updated successfully');
        closeAddEditModal();
        fetchLaureates(); // Обновляем таблицу
    } catch (error) {
        console.error('Error saving/updating laureate:', error);
        alert('Failed to save/update laureate. Please try again later.');
    }
});
// Handle add/edit form submission
document.getElementById('laureate-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        fullname: document.getElementById('fullname').value,
        sex: document.getElementById('sex').value,
        birth_year: parseInt(document.getElementById('birth_year').value),
        death_year: document.getElementById('death_year').value || null,
        country: document.getElementById('country').value,
        year: parseInt(document.getElementById('year').value),
        category: document.getElementById('category').value,
        contribution_sk: document.getElementById('contribution_sk').value || '',
        contribution_en: document.getElementById('contribution_en').value || '',
        organisation: document.getElementById('organisation').value || null
    };

    console.log('Form data to be sent:', formData);

    const url = currentLaureateId 
        ? `https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates/${currentLaureateId}` // Editing
        : 'https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates'; // Adding

    const method = currentLaureateId ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const responseText = await response.text();
        console.log('Server response:', responseText);

        if (!response.ok) {
            if (!responseText) {
                throw new Error(`HTTP error! status: ${response.status}, no response body`);
            }

            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                throw new Error(`HTTP error! status: ${response.status}, invalid JSON response`);
            }

            if (errorData.data && errorData.data.code === '23000') {
                showMessage(`Error: A laureate with the name "${formData.fullname}" already exists.`, 'error');
            } else {
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message}`);
            }
            return;
        }

        console.log('Laureate saved/updated successfully');
        showMessage('Laureate saved successfully!', 'success');
        closeAddEditModal();
        fetchLaureates(); // Refresh table
    } catch (error) {
        console.error('Error saving/updating laureate:', error);
        showMessage('Failed to save/update laureate. Please try again later.', 'error');
    }
});

// Handle JSON file upload
document.getElementById('uploadForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const fileInput = document.getElementById('jsonFile');
    const file = fileInput.files[0];

    if (!file) {
        showMessage('Please select a file first', 'error');
        return;
    }

    try {
        // 1. Read file
        const fileContent = await readFileAsText(file);
        const jsonData = JSON.parse(fileContent);

        // 2. Validate JSON structure
        if (!Array.isArray(jsonData)) {
            throw new Error("JSON must be an array of laureate objects");
        }

        if (jsonData.length === 0) {
            throw new Error("The file is empty");
        }

        // 3. Check required fields
        const requiredFields = ['fullname', 'year', 'category'];
        for (const laureate of jsonData) {
            for (const field of requiredFields) {
                if (!laureate[field]) {
                    throw new Error(`Invalid format: missing ${field} field`);
                }
            }
        }

        // 4. Load existing laureates
        const existingLaureates = await fetch('https://node36.webte.fei.stuba.sk/cv3/api/v0/laureates')
            .then(res => res.json())
            .catch(() => []);

        // 5. Filter out duplicates
        const newLaureates = jsonData.filter(newLaureate => {
            return !existingLaureates.some(existing => (
                existing.fullname === newLaureate.fullname &&
                existing.year === newLaureate.year &&
                existing.category === newLaureate.category
            ));
        });

        if (newLaureates.length === 0) {
            throw new Error("All laureates in the file already exist in the database");
        }

        // 6. Send only unique entries
        const response = await fetch('https://node36.webte.fei.stuba.sk/upload-json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newLaureates)
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || "Server error during upload");
        }

        // 7. Show success message
        showMessage(`Successfully added ${newLaureates.length} out of ${jsonData.length} laureates`, 'success');
        
        // 8. Refresh table
        if (dataTable) dataTable.destroy();
        fetchLaureates();

    } catch (error) {
        showMessage(`Error: ${error.message}`, 'error');
        console.error("Upload error:", error);
    }
});

// Helper function to read file
function readFileAsText(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = () => reject(new Error("Error reading file"));
        reader.readAsText(file, 'UTF-8');
    });
}

// Function to display styled messages
function showMessage(text, type) {
    const messageElement = document.getElementById('message');
    messageElement.textContent = text;
    messageElement.className = `message ${type}`; // Apply appropriate class
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageElement.textContent = '';
            messageElement.className = 'message';
        }, 5000);
    }
}
function toggleUploadForm() {
    const form = document.getElementById('uploadForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('jsonFile');
    const errorElement = document.getElementById('file-error');
    
    if (!fileInput.files.length) {
        e.preventDefault();
        errorElement.style.display = 'block';
        setTimeout(() => errorElement.style.display = 'none', 3000);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('uploadForm').style.display = 'none';
});


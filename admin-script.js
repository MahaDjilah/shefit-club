const MEMBERS_KEY = "shefit_members";
const CLASSES_KEY = "shefit_classes";


const initialMembers = [
    { id: 1, name: "Nour Benbadis", email: "nour@mail.com", phone: "0550000000", plan: "Bronze", date: "01-03-2026" },
    { id: 2, name: "Aya Hmid", email: "aya@mail.com", phone: "0660000000", plan: "Silver", date: "02-03-2026" },
    { id: 3, name: "Djamila Kenzi", email: "djamila@mail.com", phone: "0770000000", plan: "Gold", date: "03-03-2026" },
    { id: 4, name: "Fatma Brahimi", email: "fatma@mail.com", phone: "0551111111", plan: "Silver", date: "04-03-2026" },
    { id: 5, name: "Malika Souilah", email: "malika@mail.com", phone: "0662222222", plan: "Gold", date: "05-03-2026" }
];

const initialClasses = [
    { id: 101, name: "Pilates Core", trainer: "Sarah.J", day: "Monday", time: "10:00 AM", duration: 45, difficulty: "Beginner", capacity: 15 },
    { id: 102, name: "Kickboxing Fitness", trainer: "Maria.L", day: "Tuesday", time: "6:00 PM", duration: 50, difficulty: "Intermediate", capacity: 20 },
    { id: 103, name: "Prenatal Yoga", trainer: "Amel.D", day: "Wednesday", time: "11:00 AM", duration: 40, difficulty: "Beginner", capacity: 12 },
    { id: 104, name: "Aqua Fitness", trainer: "Lamia.S", day: "Thursday", time: "5:00 PM", duration: 45, difficulty: "Beginner", capacity: 18 },
    { id: 105, name: "Paddle Training", trainer: "Sofia.M", day: "Friday", time: "4:00 PM", duration: 60, difficulty: "Intermediate", capacity: 14 },
    { id: 106, name: "Strength Training", trainer: "Farah.B", day: "Saturday", time: "10:00 AM", duration: 50, difficulty: "Advanced", capacity: 16 }
];

let members = [];
let classes = [];


function loadData(key, initial) {
    const saved = localStorage.getItem(key);
    if (saved) return JSON.parse(saved);
    localStorage.setItem(key, JSON.stringify(initial));
    return initial;
}

function saveData(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
}


function cancelEdit(rowId) {
    if (rowId === "new-class-row") {
        const row = document.getElementById("new-class-row");
        if (row) row.remove();
    } else {
        renderAdminClasses();
        renderMembers();
    }
}


function renderMembers(filtered = members) {
    
}

function startEditMember(index, btn) { return; 
    
    const row = btn.parentElement.parentElement;
    const m = members[index];

    row.innerHTML = `
        <td><input type="text" value="${m.name}" class="edit-input edit-name"></td>
        <td><input type="email" value="${m.email}" class="edit-input edit-email"></td>   
        <td><input type="text" value="${m.date}" class="edit-input edit-date"></td>
        <td>
            <select class="edit-input edit-plan">
                <option value="Bronze" ${m.plan === "Bronze" ? "selected" : ""}>Bronze</option>
                <option value="Silver" ${m.plan === "Silver" ? "selected" : ""}>Silver</option>
                <option value="Gold"   ${m.plan === "Gold" ? "selected" : ""}>Gold</option>
            </select>
        </td>
        <td>
            <button onclick="saveEditMember(${index})" class="btn-edit">Save</button>
            <button onclick="cancelEdit()" class="btn-delete">Cancel</button>
        </td>
    `;
}

function saveEditMember(index) {
    const row = document.querySelectorAll(".activity-dashboard tbody tr")[index];
    if (!row) return;

    members[index].name = row.querySelector(".edit-name").value.trim();
    members[index].email = row.querySelector(".edit-email").value.trim();   
    members[index].date = row.querySelector(".edit-date").value.trim();
    members[index].plan = row.querySelector(".edit-plan").value;

    saveData(MEMBERS_KEY, members);
    renderMembers();
    updateDashboardStats();
    updateChart();
}

function addMember(e) {
    e.preventDefault();
    const name = document.getElementById("memberName").value.trim();
    const email = document.getElementById("memberEmail").value.trim();
    const phone = document.getElementById("memberPhone").value.trim();
    const plan = document.getElementById("memberPlan").value;
    const dateInput = document.getElementById("memberDate").value;

    if (!name || !email || !plan || !dateInput) {
        alert("Veuillez remplir tous les champs");
        return;
    }

    const date = dateInput.split("-").reverse().join("-");

    members.push({ id: Date.now(), name, email, phone, plan, date });
    saveData(MEMBERS_KEY, members);
    renderMembers();
    updateDashboardStats();
    updateChart();
    toggleForm();
    document.getElementById("memberForm").reset();
}

function deleteMember(index) {
    if (!confirm("Supprimer ce membre ?")) return;
    members.splice(index, 1);
    saveData(MEMBERS_KEY, members);
    renderMembers();
    updateDashboardStats();
    updateChart();
}

function toggleForm() {
    const form = document.getElementById("memberForm");
    if (form) form.style.display = form.style.display === "none" ? "block" : "none";
}

// ------------------CLASSES MANAGEMENT -------------------------------------------------------
function renderAdminClasses() {
    const tbody = document.getElementById("admin-classes-tbody");
    if (!tbody) return;
    tbody.innerHTML = "";

    classes.forEach((cls, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${cls.name}</td>
            <td>${cls.trainer}</td>
            <td>${cls.day}</td>
            <td>${cls.time}</td>
            <td>${cls.duration} min</td>
            <td><span class="difficulty ${cls.difficulty.toLowerCase()}">${cls.difficulty}</span></td>
            <td>${cls.capacity}</td>
            <td>
                <button onclick="startEditClass(${index}, this)" class="btn-edit">Edit</button>
                <button onclick="deleteClass(${cls.id})" class="btn-delete">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function toggleClassForm() {
    const form = document.getElementById("classForm");
    form.style.display = form.style.display === "none" ? "block" : "none";
}

function addClass(e) {
    e.preventDefault();

    const name = document.getElementById("className").value.trim();
    const trainer = document.getElementById("classTrainer").value.trim();
    const day = document.getElementById("classDay").value.trim();
    const time = document.getElementById("classTime").value.trim();
    const duration = parseInt(document.getElementById("classDuration").value);
    const difficulty = document.getElementById("classDifficulty").value;
    const capacity = parseInt(document.getElementById("classCapacity").value) || 15;

    if (!name || !trainer || !day || !time || isNaN(duration)) {
        alert("Veuillez remplir tous les champs obligatoires !");
        return;
    }

    const isDuplicate = classes.some(cls => 
        cls.trainer.toLowerCase() === trainer.toLowerCase() &&
        cls.day.toLowerCase() === day.toLowerCase() &&
        cls.time.toLowerCase() === time.toLowerCase()
    );

    if (isDuplicate) {
        alert("You can not add this Class !\n\na course with same trainer exist with same day and hour.");
        return;
    }

    classes.push({
        id: Date.now(),
        name,
        trainer,
        day,
        time,
        duration,
        difficulty,
        capacity
    });

    saveData(CLASSES_KEY, classes);
    renderAdminClasses();
    updateDashboardStats();

    toggleClassForm();
    document.getElementById("classForm").reset();

    alert("Class Added successfully");
}

function saveNewClass() {
    const name = document.getElementById("new-name").value.trim();
    const trainer = document.getElementById("new-trainer").value.trim();
    const day = document.getElementById("new-day").value.trim();
    const time = document.getElementById("new-time").value.trim();
    const duration = parseInt(document.getElementById("new-duration").value);
    const difficulty = document.getElementById("new-difficulty").value;
    const capacity = parseInt(document.getElementById("new-capacity").value) || 15;

    if (!name || !trainer || !day || !time || isNaN(duration)) {
        alert("Please fill in all required fields.");
        return;
    }

    const newClass = { id: Date.now(), name, trainer, day, time, duration, difficulty, capacity };

    if (classes.some(c => c.trainer === trainer && c.day === day && c.time === time)) {
        alert("This class already exists (same trainer, day, and time)!");
        return;
    }

    classes.push(newClass);
    saveData(CLASSES_KEY, classes);
    renderAdminClasses();
    updateDashboardStats();
}

function startEditClass(index, btn) {
    const row = btn.parentElement.parentElement;
    const cls = classes[index];

    row.innerHTML = `
        <td><input type="text" value="${cls.name}" class="edit-input edit-name"></td>
        <td><input type="text" value="${cls.trainer}" class="edit-input edit-trainer"></td>
        <td><input type="text" value="${cls.day}" class="edit-input edit-day"></td>
        <td><input type="text" value="${cls.time}" class="edit-input edit-time"></td>
        <td><input type="number" value="${cls.duration}" class="edit-input edit-duration"></td>
        <td>
            <select class="edit-input edit-difficulty">
                <option value="Beginner" ${cls.difficulty === "Beginner" ? "selected" : ""}>Beginner</option>
                <option value="Intermediate" ${cls.difficulty === "Intermediate" ? "selected" : ""}>Intermediate</option>
                <option value="Advanced" ${cls.difficulty === "Advanced" ? "selected" : ""}>Advanced</option>
            </select>
        </td>
        <td><input type="number" value="${cls.capacity}" class="edit-input edit-capacity"></td>
        <td>
            <button onclick="saveEditClass(${index})" class="btn-edit">Save</button>
            <button onclick="cancelEdit()" class="btn-delete">Cancel</button>
        </td>
    `;
}

function saveEditClass(index) {
    const row = document.querySelectorAll("#admin-classes-tbody tr")[index];
    if (!row) return;

    const updatedClass = {
        ...classes[index],
        name: row.querySelector(".edit-name").value.trim(),
        trainer: row.querySelector(".edit-trainer").value.trim(),
        day: row.querySelector(".edit-day").value.trim(),
        time: row.querySelector(".edit-time").value.trim(),
        duration: parseInt(row.querySelector(".edit-duration").value),
        difficulty: row.querySelector(".edit-difficulty").value,
        capacity: parseInt(row.querySelector(".edit-capacity").value) || classes[index].capacity
    };

    if (classes.some((c, i) => i !== index && 
        c.trainer === updatedClass.trainer && 
        c.day === updatedClass.day && 
        c.time === updatedClass.time)) {
        alert("Duplicate conflict");
        renderAdminClasses();
        return;
    }

    classes[index] = updatedClass;
    saveData(CLASSES_KEY, classes);
    renderAdminClasses();
    updateDashboardStats();
}

function deleteClass(id) {
    if (!confirm("Delete this class?")) return;
    classes = classes.filter(c => c.id !== id);
    saveData(CLASSES_KEY, classes);
    renderAdminClasses();
    updateDashboardStats();
}

// ---------------------------STATS & CHART -----------------------
function updateDashboardStats(filteredData = members) {
    
}

function updateChart(filteredData = members) {
    
    const rows = document.querySelectorAll('.member-row');
    if (!rows.length) return;

    const isFiltered = filteredData !== members;
    const counts = { Bronze: 0, Silver: 0, Gold: 0 };

    if (isFiltered) {
       
        filteredData.forEach(m => {
            if (counts[m.plan] !== undefined) counts[m.plan]++;
        });
    } else {
        
        ['Bronze','Silver','Gold'].forEach(plan => {
            const bar = document.getElementById('bar-' + plan.toLowerCase());
            if (bar && bar.dataset.count !== undefined) {
                counts[plan] = parseInt(bar.dataset.count) || 0;
            }
        });
    }

    const max = Math.max(counts.Bronze, counts.Silver, counts.Gold, 1);
    document.getElementById('bar-bronze').style.height = (counts.Bronze / max * 200) + 'px';
    document.getElementById('bar-silver').style.height = (counts.Silver / max * 200) + 'px';
    document.getElementById('bar-gold').style.height   = (counts.Gold   / max * 200) + 'px';
}


function initAdmin() {
    members = loadData(MEMBERS_KEY, initialMembers);
    classes = loadData(CLASSES_KEY, initialClasses);

    renderMembers();
    renderAdminClasses();

    
    document.getElementById("memberForm")?.addEventListener("submit", addMember);

    
    document.getElementById("add-class-btn")?.addEventListener("click", toggleClassForm);
    document.getElementById("classForm")?.addEventListener("submit", addClass);

   
    const searchInput = document.getElementById("searchMember");
    const planFilter = document.getElementById("filterPlan");

    if (searchInput) {
        searchInput.addEventListener("input", () => {
            const term = searchInput.value.toLowerCase().trim();
            const filtered = members.filter(m => 
                m.name.toLowerCase().includes(term) || 
                m.email.toLowerCase().includes(term)
            );
            renderMembers(filtered);
            updateDashboardStats(filtered);
            updateChart(filtered);
        });
    }

    if (planFilter) {
        planFilter.addEventListener("change", () => {
            const plan = planFilter.value;
            const filtered = plan === "All" 
                ? members 
                : members.filter(m => m.plan === plan);

            renderMembers(filtered);
            updateDashboardStats(filtered);
            updateChart(filtered);
        });
    }

    updateDashboardStats();
    updateChart();
    console.log("Admin Dashboard chargé (filtres + stats dynamiques)");
}


function initPublicClasses() {
    classes = loadData(CLASSES_KEY, initialClasses);
    const tbody = document.getElementById("classes-tbody");
    if (!tbody) return;

    tbody.innerHTML = "";
    classes.forEach(cls => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td><a href="#">${cls.name}</a></td>
            <td>${cls.trainer}</td>
            <td>${cls.day}</td>
            <td>${cls.time}</td>
            <td>${cls.duration} min</td>
            <td><span class="difficulty ${cls.difficulty.toLowerCase()}">${cls.difficulty}</span></td>
        `;
        tbody.appendChild(row);
    });
}


document.addEventListener("DOMContentLoaded", () => {
    if (document.title.toLowerCase().includes("admin") || document.getElementById("admin-classes-tbody")) {
        initAdmin();
    } else if (document.getElementById("classes-tbody")) {
        initPublicClasses();
    }
});

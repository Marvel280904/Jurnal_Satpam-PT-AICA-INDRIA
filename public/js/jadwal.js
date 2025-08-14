window.initJadwalForm = function () {
    const popup = document.getElementById("popupAdd");
    const popupLokasi = document.getElementById("popupLokasi");
    const popupShift = document.getElementById("popupShift");
    const popupAddBtn = document.getElementById("popupAddBtn");
    const popupCancel = document.getElementById("popupCancel");
    const searchInput = document.getElementById("searchSatpam");
    const satpamList = document.getElementById("satpamList");
    const form = document.getElementById("jadwalForm");

    let currentUserId = null;
    let currentUserName = null;

    // === SEARCH ===
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll(".satpam-item").forEach(item => {
                const name = item.querySelector("span")?.textContent?.toLowerCase() || "";
                item.style.display = name.includes(val) ? "" : "none";
            });
        });
    }

    // === OPEN POPUP ADD TO ===
    document.querySelectorAll(".add-icon").forEach(icon => {
        icon.addEventListener("click", function () {
            const parent = this.closest(".satpam-item");
            currentUserId = parent.dataset.user;
            currentUserName = parent.querySelector("span").textContent;
            popup.style.display = "flex";
        });
    });

    // === CANCEL ADD TO POPUP ===
    popupCancel?.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        popup.style.display = "none";
    });

    // === CLOSE ADD TO POPUP BY OUTSIDE CLICK ===
    popup.addEventListener("click", function (e) {
        if (!e.target.closest('.popup-content')) {
            this.style.display = "none";
        }
    });

    // === ADD TO SHIFT ===
    popupAddBtn?.addEventListener("click", function () {
        const lokasiId = popupLokasi.value;
        const shiftName = popupShift.value;

        const targetBlock = document.querySelector(`.shift-block[data-lokasi="${lokasiId}"][data-shift="${shiftName}"] .assigned-list`);
        if (targetBlock) {
            const newItem = document.createElement("div");
            newItem.classList.add("assigned-item");
            newItem.dataset.user = currentUserId;
            newItem.dataset.username = currentUserName;
            newItem.innerHTML = `<span>${currentUserName}</span><i class="bi bi-x remove-icon"></i>`;
            targetBlock.appendChild(newItem);

            document.querySelector(`.satpam-item[data-user="${currentUserId}"]`)?.remove();

            newItem.querySelector(".remove-icon").addEventListener("click", function () {
                newItem.remove();
                restoreSatpam(newItem.dataset.user, newItem.dataset.username);
            });
        }

        popup.style.display = "none";
    });

    // === RESTORE SATPAM TO LIST ===
    function restoreSatpam(id, name) {
        const newItem = document.createElement("div");
        newItem.classList.add("satpam-item");
        newItem.dataset.user = id;
        newItem.innerHTML = `<span>${name}</span><i class="bi bi-person-fill-check add-icon" title="Add"></i>`;
        satpamList.appendChild(newItem);

        newItem.querySelector(".add-icon").addEventListener("click", function () {
            currentUserId = id;
            currentUserName = name;
            popup.style.display = "flex";
        });
    }

    // === FORM SUBMIT ===
    form?.addEventListener("submit", function (e) {
        e.preventDefault();

        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;

        if (!startDate || !endDate) {
            alert("Tanggal mulai dan akhir wajib diisi.");
            return;
        }

        let assignData = {};
        document.querySelectorAll(".shift-block").forEach(block => {
            const lokasiId = block.dataset.lokasi;
            const shift = block.dataset.shift;
            const userIds = Array.from(block.querySelectorAll(".assigned-item")).map(item => item.dataset.user);
            if (!assignData[lokasiId]) assignData[lokasiId] = {};
            assignData[lokasiId][shift] = userIds;
        });

        const unassigned = Array.from(document.querySelectorAll(".satpam-item")).map(item => item.dataset.user);
        if (unassigned.length > 0) {
            if (!assignData["null"]) assignData["null"] = {};
            assignData["null"]["null"] = unassigned;
        }

        document.getElementById("assignData").value = JSON.stringify(assignData);

        // === AJAX SUBMIT ===
        fetch(`/jadwal/check-date?start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    alert("Jadwal pada rentang tanggal ini sudah ada!");
                } else {
                    const payload = new FormData(form);
                    fetch("/jadwal/store", {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: payload
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success || data.status === "ok") {
                            alert("Jadwal berhasil ditambahkan!");
                            document.getElementById("jadwalModal").style.display = "none";
                            document.getElementById("jadwalModalBody").innerHTML = "";
                            window.location.href = "/guard-data";
                        } else {
                            alert("Gagal menyimpan jadwal.");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Terjadi kesalahan.");
                    });
                    console.warn("Form jadwalForm belum tersedia di DOM.");
                }
            })
            .catch(() => alert("Gagal mengecek jadwal."));
    });
};

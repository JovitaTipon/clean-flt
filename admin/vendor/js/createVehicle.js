document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#createVehicleForm");

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch("add-vehicle.php", {
                method: "POST",
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                swal("Success!", "Vehicle has been created!", "success");

                // Append new vehicle to the table (assumes table ID = dataTable)
                const table = document.querySelector("#dataTable tbody");
                const newRow = document.createElement("tr");
                newRow.innerHTML = `
                    <td>${data.vehicle.id}</td>
                    <td>${data.vehicle.name}</td>
                    <td>${data.vehicle.reg_no}</td>
                    <td>${data.vehicle.driver}</td>
                    <td>
                        <a href="admin-manage-single-vehicle.php?v_id=${data.vehicle.id}" class="badge badge-success">Update</a>
                        <a href="admin-manage-vehicle.php?del=${data.vehicle.id}" class="badge badge-danger">Delete</a>
                    </td>
                `;
                table.appendChild(newRow);

                // Optionally, close modal
                $('#createVehicleModal').modal('hide');
                form.reset();
            } else {
                swal("Error!", data.message || "Something went wrong.", "error");
            }

        } catch (err) {
            console.error(err);
            swal("Error!", "Request failed.", "error");
        }
    });
});

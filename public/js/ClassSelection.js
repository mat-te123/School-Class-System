document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form[data-has-submission]");
    const selects = Array.from(document.querySelectorAll(".select-primary"));
    const hint = document.getElementById("no-change-hint");

    if (!form || selects.length === 0) return;

    const initialValues = selects.map((select) => select.value);
    const submitButton = form.querySelector('button[type="submit"]');
    const deleteButton = document.getElementById("delete-botton");

    function updateUnavailableOptions() {
        const selectedValues = new Set(
            selects
                .map((select) => select.value)
                .filter((value) => value !== ""),
        );

        selects.forEach((select) => {
            Array.from(select.options).forEach((option) => {
                if (option.value === "") return;

                option.disabled =
                    selectedValues.has(option.value) &&
                    option.value !== select.value;
            });
        });
    }

    function updateSubmitState() {
        if (form.dataset.hasSubmission !== "true" || !submitButton) return;

        const currentValues = selects.map((select) => select.value);
        const isUnchanged =
            JSON.stringify(currentValues) === JSON.stringify(initialValues);

        function disableButton(object) {
            object.disabled = isUnchanged;
            object.classList.toggle("cursor-not-allowed", isUnchanged);
            object.classList.toggle("opacity-50", isUnchanged);
        }

        disableButton(submitButton);
        // disableButton(deleteButton);
        hint.classList.toggle("hidden", !isUnchanged);
    }

    selects.forEach((select) => {
        select.addEventListener("change", updateUnavailableOptions);
    });

    selects.forEach((select) => {
        select.addEventListener("change", updateSubmitState);
    });

    if (submitButton) {
        form.addEventListener("submit", () => {
            submitButton.disabled = true;
            submitButton.classList.add("cursor-not-allowed", "opacity-50");
        });
    }

    updateUnavailableOptions();
    updateSubmitState();
});

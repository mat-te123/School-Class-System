document.addEventListener("DOMContentLoaded", () => {
    const selects = Array.from(document.querySelectorAll(".select-primary"));

    function updateUnavailableOptions() {
        const selectedValues = new Set(
            selects.map((select) => select.value).filter((value) => value !== ""),
        );

        selects.forEach((select) => {
            Array.from(select.options).forEach((option) => {
                if (option.value === "") return;

                option.disabled =
                    selectedValues.has(option.value) && option.value !== select.value;
            });
        });
    }

    selects.forEach((select) => {
        select.addEventListener("change", updateUnavailableOptions);
    });

    updateUnavailableOptions();
});

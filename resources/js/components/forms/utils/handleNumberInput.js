export const handleNumberInput = (setter) => (e) => {
    let value = e.target.value.replace(",", "."); // Zet komma om naar punt
    setter(value);
};

$(function() {

    $("#residentsTable").DataTable();

    // Initialize View Resident Modal
    $("#viewResidentModal").dialog({
      autoOpen: false,
      modal: true,
      width: 900,
      height: 600,
      resizable: true,
      classes: {
        'ui-dialog': 'rounded-lg shadow-lg',
        'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
        'ui-dialog-title': 'font-semibold',
        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
        'ui-dialog-buttonpane button': 'bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded'
      },
      show: {
        effect: "fadeIn",
        duration: 200
      },
      hide: {
        effect: "fadeOut",
        duration: 200
      },
      buttons: {
        "Close": function() {
          $(this).dialog("close");
        }
      },
      open: function() {
        // Button styling is now handled in the classes object above
      }
    });

    // View Resident Button Click Handler
    $(document).on("click", ".view-resident-btn", function() {
      const residentId = $(this).data("id");
      loadResidentForView(residentId);
      $("#viewResidentModal").dialog("open");
    });

    $("#archivedResidentsDialog").dialog({
      autoOpen: false,
      modal: true,
      width: 600,
      resizable: false,
      draggable: true,
      classes: {
        "ui-dialog": "rounded-lg shadow-xl",
        "ui-dialog-title": "font-semibold text-sm",
        "ui-dialog-buttonpane": "hidden"
      },
      open: function() {
        loadArchivedResidents();
      }
    });

    // Archive Residents Button
    $("#archiveResidentsBtn").on("click", function() {
      $("#archivedResidentsDialog").dialog("open");
    });

    // Search functionality for archived residents
    $(document).on("input", "#archivedResidentsDialog input[type='text']", function() {
      const searchTerm = $(this).val();
      loadArchivedResidents(searchTerm);
    });
  });

  // Function to load archived residents
  function loadArchivedResidents(searchTerm = '') {
    $.ajax({
      url: 'archive_api.php',
      type: 'GET',
      data: {
        search: searchTerm,
        limit: 50,
        offset: 0
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          updateArchivedResidentsTable(response.residents);
          updateArchivedResidentsFooter(response.total);
        } else {
          console.error('Failed to load archived residents:', response.message);
        }
      },
      error: function() {
        console.error('Failed to load archived residents');
      }
    });
  }

  // Function to update the archived residents table
  function updateArchivedResidentsTable(residents) {
    const tbody = $('#archivedResidentsDialog tbody');
    tbody.empty();

    if (residents.length === 0) {
      tbody.html('<tr><td colspan="4" class="p-4 text-center text-gray-500">No archived residents found</td></tr>');
      return;
    }

    residents.forEach(resident => {
      const row = `
        <tr>
          <td class="p-2 font-semibold">${resident.id.toString().padStart(3, '0')}</td>
          <td class="p-2">${resident.full_name}</td>
          <td class="p-2">${resident.archived_date}</td>
          <td class="p-2 text-center">
            <button class="restore-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm" data-id="${resident.id}" data-name="${resident.full_name}">
              Restore
            </button>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  // Function to update the footer with count
  function updateArchivedResidentsFooter(total) {
    const footer = $('#archivedResidentsDialog .border-t');
    footer.html(`<div class="px-4 py-2 text-xs text-gray-500">Showing ${total} archived resident${total !== 1 ? 's' : ''}</div>`);
  }

  // Function to load resident data for view modal
  function loadResidentForView(residentId) {
    $.getJSON(`get_resident.php?id=${residentId}`, function(data) {
      if (data.error) {
        alert("Error loading resident data: " + data.error);
        return;
      }

      // Populate view modal fields
      const fullName = [data.first_name, data.middle_name, data.last_name, data.suffix].filter(Boolean).join(' ');
      $("#view-full-name").text(fullName || '-');
      $("#view-gender").text(data.gender || '-');
      $("#view-birthdate").text(data.birthdate || '-');
      $("#view-age").text(data.birthdate ? calculateAge(data.birthdate) + ' years old' : '-');
      $("#view-birthplace").text(data.birthplace || '-');
      $("#view-contact").text(data.contact_no || '-');
      $("#view-address").text(data.address || '-');
      $("#view-civil-status").text(data.civil_status || '-');
      $("#view-religion").text(data.religion || '-');
      $("#view-citizenship").text(data.citizenship || '-');
      $("#view-voter-status").text(data.voter_status || '-');
      $("#view-occupation").text(data.occupation || '-');
      $("#view-disability-status").text(data.disability_status || '-');
      $("#view-household-id").text(data.household_display || '-');
      $("#view-remarks").text(data.remarks || '-');
    }).fail(function() {
      alert("Failed to load resident data. Please try again.");
    });
  }

  // Function to load resident data for edit modal
  function loadResidentForEdit(residentId) {
    // First load households for the dropdown
    loadHouseholdsForDropdown();

    // Then load resident data
    $.getJSON(`get_resident.php?id=${residentId}`, function(data) {
      if (data.error) {
        alert("Error loading resident data: " + data.error);
        return;
      }

      // Populate edit modal form fields
      $("#edit-resident-id").val(data.id || '');
      $("#edit-household-id").val(data.household_id || '');
      $("#edit-first-name").val(data.first_name || '');
      $("#edit-middle-name").val(data.middle_name || '');
      $("#edit-last-name").val(data.last_name || '');
      $("#edit-suffix").val(data.suffix || '');
      $("#edit-gender").val(data.gender || 'Male');
      $("#edit-birthdate").val(data.birthdate || '');
      $("#edit-birthplace").val(data.birthplace || '');
      $("#edit-civil-status").val(data.civil_status || 'Single');
      $("#edit-religion").val(data.religion || '');
      $("#edit-occupation").val(data.occupation || '');
      $("#edit-citizenship").val(data.citizenship || 'Filipino');
      $("#edit-contact-no").val(data.contact_no || '');
      $("#edit-address").val(data.address || '');
      $("#edit-voter-status").val(data.voter_status || 'No');
      $("#edit-disability-status").val(data.disability_status || 'No');
      $("#edit-remarks").val(data.remarks || '');

      // Set household search input value
      if (data.household_id && data.household_display) {
        $("#edit-household-search").val(data.household_display);
      } else {
        $("#edit-household-search").val('');
      }
    }).fail(function() {
      alert("Failed to load resident data. Please try again.");
    });
  }

  // Global variable to store households data
  let allHouseholds = [];

  // Function to load households for dropdown
  function loadHouseholdsForDropdown() {
    $.ajax({
      url: 'household_api.php',
      type: 'GET',
      data: { limit: 1000 }, // Load all households
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          allHouseholds = response.households; // Store for search functionality

          const hiddenSelect = $('#edit-household-id');
          const dropdownContainer = $('#edit-household-dropdown');

          // Clear existing options except the first one
          hiddenSelect.find('option:not(:first)').remove();
          dropdownContainer.empty();

          // Add household options to hidden select
          response.households.forEach(household => {
            const option = `<option value="${household.id}">${household.household_no} - ${household.head_name} (${household.address})</option>`;
            hiddenSelect.append(option);

            // Create visible dropdown item
            const dropdownItem = `
              <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 household-option"
                   data-id="${household.id}"
                   data-text="${household.household_no} - ${household.head_name} (${household.address})">
                <div class="font-medium text-blue-600">${household.household_no}</div>
                <div class="text-sm text-gray-600">${household.head_name} • ${household.address}</div>
                <div class="text-xs text-gray-500">${household.total_members} members</div>
              </div>
            `;
            dropdownContainer.append(dropdownItem);
          });
        }
      },
      error: function() {
        console.error('Failed to load households for dropdown');
      }
    });
  }

  // Function to load households for add modal dropdown
  function loadHouseholdsForAddModal() {
    $.ajax({
      url: 'household_api.php',
      type: 'GET',
      data: { limit: 1000 }, // Load all households
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const hiddenSelect = $('#add-household-id');
          const dropdownContainer = $('#add-household-dropdown');

          // Clear existing options except the first one
          hiddenSelect.find('option:not(:first)').remove();
          dropdownContainer.empty();

          // Add household options to hidden select
          response.households.forEach(household => {
            const option = `<option value="${household.id}">${household.household_no} - ${household.head_name} (${household.address})</option>`;
            hiddenSelect.append(option);

            // Create visible dropdown item
            const dropdownItem = `
              <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 household-option"
                   data-id="${household.id}"
                   data-text="${household.household_no} - ${household.head_name} (${household.address})">
                <div class="font-medium text-blue-600">${household.household_no}</div>
                <div class="text-sm text-gray-600">${household.head_name} • ${household.address}</div>
                <div class="text-xs text-gray-500">${household.total_members} members</div>
              </div>
            `;
            dropdownContainer.append(dropdownItem);
          });
        }
      },
      error: function() {
        console.error('Failed to load households for add modal');
      }
    });
  }

  // Function to save resident edits
  function saveResidentEdits() {
    const formData = new FormData(document.getElementById('editResidentForm'));
    const data = Object.fromEntries(formData.entries());

    $.ajax({
      url: 'update_resident.php',
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Show success message
          $('<div>' + response.message + '</div>').dialog({
            modal: true,
            title: 'Success',
            width: 420,
            buttons: {
              Ok: function() {
                $(this).dialog('close');
                $("#editResidentModal").dialog("close");
                // Refresh the page to show updated data
                location.reload();
              }
            },
            classes: {
              'ui-dialog': 'rounded-lg shadow-lg',
              'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
              'ui-dialog-title': 'font-semibold',
              'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
            }
          });
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('Failed to save resident data. Please try again.');
      }
    });
  }

  // Initialize Household Management Modal
  $("#householdManagementModal").dialog({
    autoOpen: false,
    modal: true,
    width: 800,
    height: 600,
    resizable: true,
    classes: {
      "ui-dialog": "rounded-lg shadow-xl",
      "ui-dialog-title": "font-semibold text-sm",
      "ui-dialog-buttonpane": "hidden"
    },
    open: function() {
      loadHouseholds();
    }
  });

  // Initialize Household Form Modal
  $("#householdFormModal").dialog({
    autoOpen: false,
    modal: true,
    width: 500,
    resizable: false,
    buttons: {
      "Save": function() {
        saveHousehold();
      },
      "Cancel": function() {
        $(this).dialog('close');
      }
    },
    classes: {
      'ui-dialog': 'rounded-lg shadow-lg',
      'ui-dialog-titlebar': 'bg-blue-500 text-white rounded-t-lg',
      'ui-dialog-title': 'font-semibold',
      'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
    },
    open: function() {
      $('.ui-dialog-buttonpane button:first').addClass('bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2');
      $('.ui-dialog-buttonpane button:last').addClass('bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded');
    }
  });

  // Household search and dropdown functionality
  function initializeHouseholdSearch(searchInputId, dropdownId, hiddenSelectId) {
    const searchInput = $(`#${searchInputId}`);
    const dropdown = $(`#${dropdownId}`);
    const hiddenSelect = $(`#${hiddenSelectId}`);

    // Initially hide the dropdown
    dropdown.addClass('hidden');

    // Only show dropdown on explicit click (not focus to avoid auto-focus issues)
    searchInput.on('click', function(e) {
      e.stopPropagation();
      // Only show if not already visible
      if (dropdown.hasClass('hidden')) {
        showHouseholdDropdown(dropdownId);
        filterHouseholdOptions(searchInputId, dropdownId);
      }
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
      if (!$(e.target).closest(`#${searchInputId}, #${dropdownId}`).length) {
        dropdown.addClass('hidden');
      }
    });

    // Search functionality
    searchInput.on('input', function() {
      const searchValue = $(this).val().trim();
      if (searchValue === '') {
        // Clear selection when input is empty
        hiddenSelect.val('');
        // Hide dropdown when input is cleared
        dropdown.addClass('hidden');
      } else {
        // Clear hidden value if user is typing but hasn't selected from dropdown
        // This prevents stale data when user types a new search
        const currentHiddenValue = hiddenSelect.val();
        if (currentHiddenValue && !isValidHouseholdSelection(searchInputId, dropdownId, currentHiddenValue)) {
          hiddenSelect.val('');
        }
        // Show dropdown when user starts typing
        showHouseholdDropdown(dropdownId);
      }
      filterHouseholdOptions(searchInputId, dropdownId);
    });

    // Handle option selection
    dropdown.on('click', '.household-option', function() {
      const selectedId = $(this).data('id');
      const selectedText = $(this).data('text');

      hiddenSelect.val(selectedId);
      searchInput.val(selectedText);
      dropdown.addClass('hidden'); // Hide dropdown after selection
    });
  }

  function showHouseholdDropdown(dropdownId) {
    $(`#${dropdownId}`).removeClass('hidden');
  }

  function filterHouseholdOptions(searchInputId, dropdownId) {
    const searchTerm = $(`#${searchInputId}`).val().toLowerCase();
    const options = $(`#${dropdownId} .household-option`);

    options.each(function() {
      const optionText = $(this).data('text').toLowerCase();
      if (optionText.includes(searchTerm)) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  }

  function isValidHouseholdSelection(searchInputId, dropdownId, hiddenValue) {
    const options = $(`#${dropdownId} .household-option`);
    let isValid = false;

    options.each(function() {
      if ($(this).data('id') == hiddenValue) {
        isValid = true;
        return false; // break out of each loop
      }
    });

    return isValid;
  }

  // Function to initialize resident search for head of household
  function initializeResidentSearch(searchInputId, dropdownId, hiddenId, nameId) {
    const searchInput = $(`#${searchInputId}`);
    const dropdown = $(`#${dropdownId}`);
    const hiddenInput = $(`#${hiddenId}`);
    const nameInput = $(`#${nameId}`);

    // Toggle dropdown on input focus/click
    searchInput.on('focus click', function(e) {
      e.stopPropagation();
      dropdown.removeClass('hidden');
      filterResidentOptions(searchInputId, dropdownId);
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
      if (!$(e.target).closest(`#${searchInputId}, #${dropdownId}`).length) {
        dropdown.addClass('hidden');
      }
    });

    // Search functionality
    searchInput.on('input', function() {
      filterResidentOptions(searchInputId, dropdownId);
      dropdown.removeClass('hidden');
    });

    // Handle option selection
    dropdown.on('click', '.resident-option', function() {
      const selectedId = $(this).data('id');
      const selectedName = $(this).data('name');

      hiddenInput.val(selectedId);
      nameInput.val(selectedName);
      searchInput.val(selectedName);
      dropdown.addClass('hidden');
    });
  }

  function filterResidentOptions(searchInputId, dropdownId) {
    const searchTerm = $(`#${searchInputId}`).val().toLowerCase();
    const options = $(`#${dropdownId} .resident-option`);

    options.each(function() {
      const residentName = $(this).data('name').toLowerCase();
      const residentAddress = ($(this).data('address') || '').toLowerCase();
      if (residentName.includes(searchTerm) || residentAddress.includes(searchTerm)) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  }

  // Initialize searchable dropdowns
  initializeHouseholdSearch('edit-household-search', 'edit-household-dropdown', 'edit-household-id');
  initializeHouseholdSearch('add-household-search', 'add-household-dropdown', 'add-household-id');

  // Initialize head of household search
  initializeResidentSearch('householdFormHeadSearch', 'householdFormHeadDropdown', 'householdFormHeadId', 'householdFormHead');

  // Household Management Button
  $("#manageHouseholdsBtn").on("click", function() {
    $("#householdManagementModal").dialog("open");
  });

  // Create Household Button
  $("#createHouseholdBtn").on("click", function() {
    resetHouseholdForm();
    loadResidentsForHeadSelection();
    $("#householdFormModal").dialog("option", "title", "Create Household");
    $("#householdFormModal").dialog("open");
  });

  // Search functionality
  $("#householdSearchInput").on("input", function() {
    const searchTerm = $(this).val();
    loadHouseholds(searchTerm);
  });

  // Function to load households
  function loadHouseholds(searchTerm = '') {
    $.ajax({
      url: 'household_api.php',
      type: 'GET',
      data: {
        search: searchTerm,
        limit: 100
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          renderHouseholdList(response.households);
        } else {
          console.error('Failed to load households:', response.message);
        }
      },
      error: function() {
        console.error('Failed to load households');
      }
    });
  }

  // Function to render household list
  function renderHouseholdList(households) {
    const container = $('#householdList');
    container.empty();

    if (households.length === 0) {
      container.html('<div class="p-4 text-center text-gray-500">No households found</div>');
      return;
    }

    households.forEach(household => {
      const item = `
        <div class="border-b p-4 hover:bg-gray-50">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="font-semibold text-blue-600">${household.household_no}</div>
              <div class="text-sm text-gray-600 mt-1">
                <div>🏠 ${household.address}</div>
                <div>👤 Head: ${household.head_name}</div>
                <div>👥 Members: ${household.total_members}</div>
              </div>
            </div>
          </div>
        </div>
      `;
      container.append(item);
    });

  // Function to reset household form
  function resetHouseholdForm() {
    $('#householdForm')[0].reset();
    $('#householdFormId').val('');
    $('#householdFormHeadId').val('');
    $('#householdFormHeadSearch').val('');
    // Show head selection for new households
    $('#householdFormHeadContainer').show();
    $('#householdFormHeadSearch').prop('required', true);
  }

  // Function to load residents for head of household selection
  function loadResidentsForHeadSelection() {
    $.ajax({
      url: 'get_residents_for_head.php',
      type: 'GET',
      data: { limit: 1000 },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const dropdownContainer = $('#householdFormHeadDropdown');
          dropdownContainer.empty();

          response.residents.forEach(resident => {
            const fullName = [resident.first_name, resident.middle_name, resident.last_name, resident.suffix].filter(Boolean).join(' ');
            const residentItem = `
              <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 resident-option"
                   data-id="${resident.id}"
                   data-name="${fullName}"
                   data-address="${resident.address || ''}">
                <div class="font-medium text-blue-600">${fullName}</div>
                <div class="text-sm text-gray-600">${resident.address || 'No address'}</div>
                <div class="text-xs text-gray-500">Age: ${resident.age || 'Unknown'}</div>
              </div>
            `;
            dropdownContainer.append(residentItem);
          });
        }
      },
      error: function() {
        console.error('Failed to load residents for head selection');
      }
    });
  }

  // Helper function to calculate age
  function calculateAge(birthdate) {
    if (!birthdate) return null;
    const parts = birthdate.split('-');
    if (parts.length !== 3) return null;
    const birthDate = new Date(parts[0], parts[1] - 1, parts[2]);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age;
  }
}
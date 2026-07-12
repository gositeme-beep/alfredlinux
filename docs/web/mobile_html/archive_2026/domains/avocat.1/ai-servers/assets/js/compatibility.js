/**
 * Compatibility rules for AI Server Configurator.
 * All products must have been loaded; build = { gpu: id, cpu: id, motherboard: id, ram: id, storage: id, psu: id, case: id }.
 */
window.AIServersCompat = (function () {
  function getProduct(products, category, id) {
    var list = products[category];
    if (!list) return null;
    return list.find(function (p) { return p.id === id; }) || null;
  }

  function cpuSocket(products, build) {
    var cpu = getProduct(products, 'cpus', build.cpu);
    return cpu ? (cpu.compatibility && cpu.compatibility.socket) : null;
  }

  function mbFormFactor(products, build) {
    var mb = getProduct(products, 'motherboards', build.motherboard);
    if (!mb || !mb.specs) return null;
    var ff = (mb.specs.formFactor || '').toLowerCase().replace(/-/g, '');
    if (ff === 'ssieeb') return 'ssi-eeb';
    return ff || null;
  }

  function caseFormFactors(products, build) {
    var c = getProduct(products, 'cases', build.case);
    if (!c || !c.compatibility || !c.compatibility.supportedFormFactors) return [];
    return c.compatibility.supportedFormFactors.map(function (f) { return (f || '').toLowerCase(); });
  }

  function caseMaxGpuLength(products, build) {
    var c = getProduct(products, 'cases', build.case);
    return (c && c.compatibility && c.compatibility.maxGpuLengthMm) ? c.compatibility.maxGpuLengthMm : 9999;
  }

  function mbMaxRam(products, build) {
    var mb = getProduct(products, 'motherboards', build.motherboard);
    return (mb && mb.compatibility && mb.compatibility.maxRamGb) ? mb.compatibility.maxRamGb : 0;
  }

  function mbRamSlots(products, build) {
    var mb = getProduct(products, 'motherboards', build.motherboard);
    return (mb && mb.compatibility && mb.compatibility.ramSlots) ? mb.compatibility.ramSlots : 4;
  }

  function mbMemoryType(products, build) {
    var mb = getProduct(products, 'motherboards', build.motherboard);
    return (mb && mb.specs && mb.specs.memoryType) ? mb.specs.memoryType : null;
  }

  /** GPU fits case (length) and form factor. */
  function gpuFitsCase(products, build, gpu) {
    if (!build.case) return true;
    var maxLen = caseMaxGpuLength(products, build);
    var allowed = caseFormFactors(products, build);
    var mbFf = mbFormFactor(products, build);
    if (mbFf && allowed.length && allowed.indexOf(mbFf) === -1) return false;
    var len = (gpu.specs && gpu.specs.lengthMm) ? gpu.specs.lengthMm : 400;
    return len <= maxLen;
  }

  /** CPU matches motherboard socket. */
  function cpuFitsMb(cpu, mb) {
    if (!mb || !cpu) return false;
    var socket = (cpu.compatibility && cpu.compatibility.socket) || '';
    var mbSocket = (mb.specs && mb.specs.socket) || (mb.compatibility && mb.compatibility.socket) || '';
    return socket === mbSocket;
  }

  /** Motherboard form factor supported by case. */
  function mbFitsCase(mb, caseProd) {
    if (!caseProd || !mb) return true;
    var supported = (caseProd.compatibility && caseProd.compatibility.supportedFormFactors) || [];
    var mbFf = (mb.specs && mb.specs.formFactor) ? mb.specs.formFactor.toLowerCase().replace(/\s/g, '').replace(/-/g, '') : '';
    if (mbFf === 'ssieeb') mbFf = 'ssieeb';
    return supported.some(function (f) {
      var s = (f || '').toLowerCase().replace(/\s/g, '').replace(/-/g, '');
      return s === mbFf || (s === 'ssi-eeb' && mbFf === 'ssieeb');
    });
  }

  /** RAM type and form factor match board; capacity within max and slot count. */
  function ramFitsMb(ram, mb, currentRamId) {
    if (!mb) return true;
    var type = (ram.specs && ram.specs.type) || (ram.compatibility && ram.compatibility.memoryType) || '';
    var mbType = (mb.specs && mb.specs.memoryType) || '';
    if (mbType && type && mbType.toLowerCase().indexOf('ecc') !== -1 && type.toLowerCase().indexOf('ecc') === -1) return false;
    if (mbType && type && mbType.toLowerCase().indexOf('ecc') === -1 && type.toLowerCase().indexOf('ecc') !== -1) return false;
    var slots = (mb.compatibility && mb.compatibility.ramSlots) || (mb.specs && mb.specs.ramSlots) || 4;
    var sticks = (ram.specs && ram.specs.sticks) || 1;
    return sticks <= slots;
  }

  function getFiltered(products, build, category) {
    var list = products[category];
    if (!list) return [];
    var mb = build.motherboard ? getProduct(products, 'motherboards', build.motherboard) : null;
    var caseProd = build.case ? getProduct(products, 'cases', build.case) : null;
    var cpuSocketVal = cpuSocket(products, build);
    var caseFfs = caseFormFactors(products, build);
    var caseMaxLen = caseMaxGpuLength(products, build);
    var mbMaxRamGb = mbMaxRam(products, build);
    var mbSlots = mbRamSlots(products, build);
    var mbMemType = mbMemoryType(products, build);

    return list.filter(function (p) {
      if (category === 'gpus') {
        if (build.case && !gpuFitsCase(products, build, p)) return false;
        return true;
      }
      if (category === 'cpus') {
        if (!mb) return true;
        return cpuFitsMb(p, mb);
      }
      if (category === 'motherboards') {
        if (build.cpu) {
          var cpu = getProduct(products, 'cpus', build.cpu);
          if (!cpuFitsMb(cpu, p)) return false;
        }
        if (caseProd && !mbFitsCase(p, caseProd)) return false;
        return true;
      }
      if (category === 'ram') {
        if (!mb) return true;
        if (mbMemType && p.specs && p.specs.type) {
          var mt = (mbMemType + '').toLowerCase();
          var pt = (p.specs.type + '').toLowerCase();
          if (mt.indexOf('ecc') !== -1 && pt.indexOf('ecc') === -1) return false;
          if (mt.indexOf('ecc') === -1 && pt.indexOf('ecc') !== -1) return false;
        }
        var sticks = (p.specs && p.specs.sticks) || 1;
        return sticks <= mbSlots;
      }
      if (category === 'cases') {
        if (build.motherboard) {
          var mb2 = getProduct(products, 'motherboards', build.motherboard);
          if (mb2 && !mbFitsCase(mb2, p)) return false;
        }
        return true;
      }
      if (category === 'storage' || category === 'psus') return true;
      return true;
    });
  }

  function getWarnings(products, build) {
    var w = [];
    var mb = build.motherboard ? getProduct(products, 'motherboards', build.motherboard) : null;
    var ram = build.ram ? getProduct(products, 'ram', build.ram) : null;
    var gpu = build.gpu ? getProduct(products, 'gpus', build.gpu) : null;
    var mbMax = mbMaxRam(products, build);
    if (mb && ram && ram.specs && ram.specs.capacityGb > mbMax) {
      w.push('RAM capacity (' + ram.specs.capacityGb + 'GB) exceeds board max (' + mbMax + 'GB).');
    }
    return w;
  }

  return {
    getProduct: getProduct,
    getFiltered: getFiltered,
    getWarnings: getWarnings,
    cpuSocket: cpuSocket,
    mbFormFactor: mbFormFactor,
    caseMaxGpuLength: caseMaxGpuLength,
    mbMaxRam: mbMaxRam
  };
})();

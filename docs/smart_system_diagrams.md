# Smart System Diagrams

Each subsection contains a high-level block diagram and an operational flowchart drawn with Mermaid. Render them in any Markdown viewer that supports Mermaid to see the visuals.

---

## Smart waste management system

**Block Diagram**
```mermaid
graph LR
    bin[Smart Bin Sensors\n(ultrasonic, weight, GPS)]
    mcu[Edge MCU/\nController]
    net[LPWAN/\nCellular Gateway]
    cloud[Cloud Platform\nAnalytics & Storage]
    dash[Municipal Dashboard]
    fleet[Collection Fleet]
    bin --> mcu --> net --> cloud --> dash --> fleet
    dash -->|Route updates| fleet
```

**Flowchart**
```mermaid
flowchart TD
    start([Start])
    read[Sample bin level & status]
    check{Fill level ≥ threshold?}
    alert[Send pickup alert\n& geolocation]
    plan[Optimize pickup route\nand schedule]
    log[Log measurements\nand state]
    wait[Sleep / wait for next cycle]
    start --> read --> check
    check -- Yes --> alert --> plan --> log --> wait --> start
    check -- No --> log --> wait --> start
```

---

## Smart water level monitoring system

**Block Diagram**
```mermaid
graph LR
    sensor[Ultrasonic / Pressure\nLevel Sensors]
    ctrl[Low-power MCU\nwith ADC]
    power[Power Module\n(Solar + Battery)]
    comm[IoT Communication\n(LPWAN/Wi-Fi)]
    cloud[Cloud Server\nReal-time Dashboard]
    user[User Alerts\n(SMS/App)]
    sensor --> ctrl
    power --> ctrl
    ctrl --> comm --> cloud --> user
```

**Flowchart**
```mermaid
flowchart TD
    start([Start])
    measure[Measure water level]
    validate{Sensor data valid?}
    retry[Re-measure / fallback sensor]
    compare{Level beyond bounds?}
    alarm[Trigger alarm & notifications]
    store[Store data in cloud]
    wait[Delay based on sampling rate]
    start --> measure --> validate
    validate -- No --> retry --> measure
    validate -- Yes --> compare
    compare -- Yes --> alarm --> store --> wait --> start
    compare -- No --> store --> wait --> start
```

---

## Smart car parking system

**Block Diagram**
```mermaid
graph LR
    entry[Entry/Exit Sensors]
    slot[Slot Occupancy Sensors]
    ctrl[Parking Controller\n(Edge Server)]
    sign[LED Guidance Display]
    pay[Payment Terminal/App]
    cloud[Cloud Mgmt & DB]
    security[Security/Operator Console]
    entry --> ctrl
    slot --> ctrl
    ctrl --> sign
    ctrl --> pay
    ctrl --> cloud --> security
```

**Flowchart**
```mermaid
flowchart TD
    start([Vehicle arrives])
    detect[Detect vehicle at gate]
    checkSlots{Slot available?}
    assign[Assign slot & raise barrier]
    guide[Show guidance on display/app]
    occupy[Monitor slot occupancy]
    pay[Process payment / validation]
    release[Update database & free slot]
    deny[Deny entry & display FULL]
    detect --> checkSlots
    checkSlots -- Yes --> assign --> guide --> occupy --> pay --> release --> detect
    checkSlots -- No --> deny --> detect
```

---

## Smart classroom monitoring system

**Block Diagram**
```mermaid
graph LR
    env[Environment Sensors\n(CO₂, temp, light)]
    attendance[Attendance Devices\n(RFID/Face ID)]
    cam[IP Cameras]
    edge[Edge Gateway]
    cloud[School Cloud\nAnalytics & DB]
    teacher[Teacher Dashboard]
    hvac[Smart HVAC / Lighting]
    env --> edge
    attendance --> edge
    cam --> edge
    edge --> cloud --> teacher
    teacher --> hvac
```

**Flowchart**
```mermaid
flowchart TD
    start([Start Class])
    capture[Capture attendance & sensor data]
    eval{Anomaly or threshold breach?}
    notify[Notify teacher/admin]
    actuate[Adjust HVAC / lighting / alerts]
    log[Log data & insights]
    next[Next sampling interval]
    start --> capture --> eval
    eval -- Yes --> notify --> actuate --> log --> next --> capture
    eval -- No --> log --> next --> capture
```

---

## Smart rail accident prevention system

**Block Diagram**
```mermaid
graph LR
    track[Track & Obstacle Sensors]
    onboard[Onboard Controller\n(Train ECU)]
    v2x[V2X / LTE-R Communication]
    ctrl[Rail Control Center]
    alert[Driver HMI & Auto Brake]
    emergency[Emergency Services]
    track --> onboard --> v2x --> ctrl --> emergency
    ctrl --> alert
    onboard --> alert
```

**Flowchart**
```mermaid
flowchart TD
    start([Train in motion])
    scan[Scan track & train health]
    detect{Obstacle / fault detected?}
    warn[Warn driver & control center]
    brake[Engage automatic braking]
    dispatch[Dispatch maintenance/emergency]
    log[Record incident]
    resume[Resume monitoring]
    scan --> detect
    detect -- Yes --> warn --> brake --> dispatch --> log --> resume --> scan
    detect -- No --> resume --> scan
```

---

## Smart laboratory management system

**Block Diagram**
```mermaid
graph LR
    access[Access Control\n(RFID/Biometrics)]
    inventory[Inventory Sensors\n(Weight, RFID tags)]
    equip[Smart Equipment\nStatus Feeds]
    edge[Lab Controller / Gateway]
    db[Lab Management Server & DB]
    ui[Lab Manager Portal]
    vendors[Supplier API]
    access --> edge --> db --> ui
    inventory --> edge
    equip --> edge
    db --> vendors
```

**Flowchart**
```mermaid
flowchart TD
    start([Start of session])
    auth[Authenticate user & log entry]
    record[Record equipment usage]
    updateInv[Update chemical/equipment inventory]
    checkLvl{Stock below reorder point?}
    reorder[Trigger vendor reorder request]
    report[Generate usage & safety report]
    end([End session / standby])
    start --> auth --> record --> updateInv --> checkLvl
    checkLvl -- Yes --> reorder --> report --> end --> start
    checkLvl -- No --> report --> end --> start
```

---

## Home automation system

**Block Diagram**
```mermaid
graph LR
    sensors[Home Sensors\n(temp, motion, doors)]
    hub[Smart Hub / Edge Controller]
    cloud[Cloud Services\nVoice Assistants]
    actuators[Actuators\n(lights, HVAC, locks)]
    user[Mobile App / Voice UI]
    sensors --> hub --> actuators
    hub --> cloud --> user
    user --> hub
```

**Flowchart**
```mermaid
flowchart TD
    start([System Idle])
    poll[Poll sensors / receive triggers]
    evaluate{Rule or automation matched?}
    action[Execute actuator command]
    notify[Send notification to user]
    learn[Log data / adjust scenes]
    wait[Wait for next event]
    start --> poll --> evaluate
    evaluate -- Yes --> action --> notify --> learn --> wait --> poll
    evaluate -- No --> wait --> poll
```

---

## Industrial hazard prevention system

**Block Diagram**
```mermaid
graph LR
    sensors[Gas, Flame, Vibration\nSensors]
    plc[PLC / Safety Controller]
    scada[SCADA Server\nAnalytics]
    alarms[Audible/Visual Alarms]
    suppression[Suppression Systems]
    hq[Safety Team Dashboard]
    sensors --> plc --> scada --> hq
    plc --> alarms
    plc --> suppression
```

**Flowchart**
```mermaid
flowchart TD
    start([Continuous monitoring])
    acquire[Acquire multi-sensor data]
    diagnose{Hazard threshold exceeded?}
    alarm[Activate alarms & beacons]
    mitigate[Trigger suppression / shutdown]
    notify[Notify safety team]
    audit[Log event & diagnostics]
    resume[Return to monitoring]
    acquire --> diagnose
    diagnose -- Yes --> alarm --> mitigate --> notify --> audit --> resume --> acquire
    diagnose -- No --> resume --> acquire
```

---

## Obstacle avoidance robot

**Block Diagram**
```mermaid
graph LR
    sensors[Lidar/Ultrasonic/IR Sensors]
    mcu[Microcontroller\n(Path Planning)]
    motor[Motor Driver]
    wheels[Drive Motors]
    feedback[Encoders / IMU]
    power[Battery Management]
    sensors --> mcu --> motor --> wheels
    wheels --> feedback --> mcu
    power --> mcu
```

**Flowchart**
```mermaid
flowchart TD
    start([Start / Power On])
    readSensors[Read obstacle sensors]
    analyze{Obstacle ahead?}
    plan[Compute avoidance maneuver]
    move[Drive motors]
    adjust[Update heading & speed]
    loop[Loop until goal reached]
    readSensors --> analyze
    analyze -- Yes --> plan --> move --> adjust --> readSensors
    analyze -- No --> move --> adjust --> readSensors
    adjust --> loop
```

---

## Solar tracking system

**Block Diagram**
```mermaid
graph LR
    ldr[LDR/Photodiode\nLight Sensors]
    ctrl[Microcontroller]
    driver[Servo / Motor Driver]
    panel[Solar Panel Array]
    monitor[Energy Monitor\n& Data Logger]
    power[Battery & Regulator]
    ldr --> ctrl --> driver --> panel
    panel --> monitor --> ctrl
    power --> ctrl
```

**Flowchart**
```mermaid
flowchart TD
    start([Start])
    sample[Sample east & west light sensors]
    compare{Difference > deadband?}
    adjust[Rotate panel toward brighter side]
    log[Log position & power output]
    wait[Delay for stabilization]
    start --> sample --> compare
    compare -- Yes --> adjust --> log --> wait --> sample
    compare -- No --> log --> wait --> sample
```


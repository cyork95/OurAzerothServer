--[[
    Pet Storage Manager (Pet Bank)
    Allows hunters to store up to 50 additional pets.
    Uses database slots 10-60 to bypass client UI limits.
]]

local NPC_ENTRY = 99999
local BANK_SLOT_MIN = 10
local BANK_SLOT_MAX = 60
local STABLE_SLOT_MIN = 1
local STABLE_SLOT_MAX = 4

local function OnGossipHello(event, player, creature)
    if player:GetClass() ~= 3 then -- Hunter
        player:GossipMenuAddItem(0, "I only speak with Hunters.", 0, 100)
        player:GossipSendMenu(1, creature)
        return
    end

    player:GossipMenuAddItem(0, "Deposit a pet into the Bank", 0, 1)
    player:GossipMenuAddItem(0, "Withdraw a pet from the Bank", 0, 2)
    player:GossipSendMenu(1, creature)
end

local function OnGossipSelect(event, player, creature, sender, intid, code)
    if intid == 100 then
        player:GossipComplete()
        return
    end

    -- Deposit Menu
    if intid == 1 then
        local Q = CharDBQuery(string.format("SELECT id, name, entry, slot FROM character_pet WHERE owner = %d AND slot BETWEEN %d AND %d", player:GetGUIDLow(), STABLE_SLOT_MIN, STABLE_SLOT_MAX))
        if Q then
            repeat
                local petId = Q:GetUInt32(0)
                local petName = Q:GetString(1)
                player:GossipMenuAddItem(0, "Deposit " .. petName, 0, 10 + petId)
            until not Q:NextRow()
            player:GossipSendMenu(1, creature)
        else
            player:SendBroadcastMessage("You have no pets in your stable to deposit.")
            player:GossipComplete()
        end

    -- Withdraw Menu
    elseif intid == 2 then
        local Q = CharDBQuery(string.format("SELECT id, name, entry, slot FROM character_pet WHERE owner = %d AND slot BETWEEN %d AND %d", player:GetGUIDLow(), BANK_SLOT_MIN, BANK_SLOT_MAX))
        if Q then
            repeat
                local petId = Q:GetUInt32(0)
                local petName = Q:GetString(1)
                player:GossipMenuAddItem(0, "Withdraw " .. petName, 0, 1000 + petId)
            until not Q:NextRow()
            player:GossipSendMenu(1, creature)
        else
            player:SendBroadcastMessage("You have no pets in the bank.")
            player:GossipComplete()
        end

    -- Process Deposit
    elseif intid > 10 and intid < 1000 then
        local petId = intid - 10
        -- Find free bank slot
        local Q = CharDBQuery(string.format("SELECT slot FROM character_pet WHERE owner = %d AND slot BETWEEN %d AND %d", player:GetGUIDLow(), BANK_SLOT_MIN, BANK_SLOT_MAX))
        local usedSlots = {}
        if Q then
            repeat
                usedSlots[Q:GetUInt32(0)] = true
            until not Q:NextRow()
        end

        local targetSlot = -1
        for i = BANK_SLOT_MIN, BANK_SLOT_MAX do
            if not usedSlots[i] then
                targetSlot = i
                break
            end
        end

        if targetSlot ~= -1 then
            CharDBExecute(string.format("UPDATE character_pet SET slot = %d WHERE id = %d", targetSlot, petId))
            player:SendBroadcastMessage("Pet deposited successfully.")
        else
            player:SendBroadcastMessage("Your pet bank is full!")
        end
        player:GossipComplete()

    -- Process Withdrawal
    elseif intid >= 1000 then
        local petId = intid - 1000
        -- Check for free stable slot
        local Q = CharDBQuery(string.format("SELECT slot FROM character_pet WHERE owner = %d AND slot BETWEEN %d AND %d", player:GetGUIDLow(), STABLE_SLOT_MIN, STABLE_SLOT_MAX))
        local usedSlots = {}
        if Q then
            repeat
                usedSlots[Q:GetUInt32(0)] = true
            until not Q:NextRow()
        end

        local targetSlot = -1
        for i = STABLE_SLOT_MIN, STABLE_SLOT_MAX do
            if not usedSlots[i] then
                targetSlot = i
                break
            end
        end

        if targetSlot ~= -1 then
            CharDBExecute(string.format("UPDATE character_pet SET slot = %d WHERE id = %d", targetSlot, petId))
            player:SendBroadcastMessage("Pet withdrawn to stable successfully.")
        else
            player:SendBroadcastMessage("Your stable is full! Please make room before withdrawing.")
        end
        player:GossipComplete()
    end
end

RegisterCreatureGossipEvent(NPC_ENTRY, 1, OnGossipHello)
RegisterCreatureGossipEvent(NPC_ENTRY, 2, OnGossipSelect)
print(">> Pet Storage Manager (Bank) Loaded.")

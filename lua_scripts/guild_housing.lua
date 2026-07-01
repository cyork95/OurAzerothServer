--[[
    OurAzeroth Guild Housing System
    Beta Prototype
]]

local GUILD_HOUSING_MIN_MEMBERS = 1000
local COMMAND_PREFIX = "guild"

-- Base Templates (Coordinates)
-- Placeholder: Using Northshire and Valley of Trials as initial beta locations
local BASE_TEMPLATES = {
    [0] = {map = 0, x = -8920.6, y = -178.2, z = 80.9, o = 0, name = "Alliance Base"}, -- Alliance Team (0)
    [1] = {map = 1, x = -658.9, y = -4311.9, z = 45.7, o = 0, name = "Horde Base"},   -- Horde Team (1)
}

local function OnGuildHouseCommand(event, player, command)
    if (command == "house") then
        -- Combat Check
        if player:IsInCombat() then
            player:SendBroadcastMessage("You cannot teleport to the guild base while in combat!")
            return false
        end

        -- Battleground/Arena Check
        local map = player:GetMap()
        if map:IsBattleground() or map:IsArena() then
            player:SendBroadcastMessage("You cannot teleport to the guild base from a battleground or arena!")
            return false
        end

        local guild = player:GetGuild()
        if not guild then
            player:SendBroadcastMessage("You are not in a guild.")
            return false
        end

        local memberCount = guild:GetMemberCount()
        if (memberCount <= GUILD_HOUSING_MIN_MEMBERS) then
            player:SendBroadcastMessage(string.format("Your guild does not meet the requirements for a Guild Base. Need more than %d members, currently have %d.", GUILD_HOUSING_MIN_MEMBERS, memberCount))
            return false
        end

        -- Determine base template by team
        local team = player:GetTeam() -- 0 for Alliance, 1 for Horde
        local template = BASE_TEMPLATES[team]

        if not template then
            player:SendBroadcastMessage("Error: No base template found for your team.")
            return false
        end

        player:SendBroadcastMessage(string.format("Teleporting to %s...", template.name))
        player:Teleport(template.map, template.x, template.y, template.z, template.o)
        return false
    end
end

-- Register the custom command handler
-- Using a custom chat hook since Eluna doesn't support direct registration of .subcommands in all builds
local function OnPlayerChat(event, player, msg, Type, lang)
    if (msg:sub(1, 7) == "." .. COMMAND_PREFIX .. " ") then
        local subCommand = msg:sub(8)
        return OnGuildHouseCommand(event, player, subCommand)
    end
end

RegisterPlayerEvent(18, OnPlayerChat) -- PLAYER_EVENT_ON_CHAT
print(">> OurAzeroth Guild Housing System Loaded.")

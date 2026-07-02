if not C_Timer then
    C_Timer = {}
    local timers = {}
    local timerFrame = CreateFrame("Frame", "GuidelimeCTimerFrame", UIParent)
    
    timerFrame:SetScript("OnUpdate", function(self, elapsed)
        local i = 1
        while i <= #timers do
            local timer = timers[i]
            timer.delay = timer.delay - elapsed
            if timer.delay <= 0 then
                table.remove(timers, i)
                -- Execute callback safely to prevent crashes from cascading
                local success, err = pcall(timer.callback)
                if not success then
                    geterrorhandler()(err)
                end
            else
                i = i + 1
            end
        end
    end)
    
    function C_Timer.After(delay, callback)
        if type(callback) ~= "function" then return end
        table.insert(timers, { delay = delay, callback = callback })
    end
end
